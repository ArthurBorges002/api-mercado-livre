<?php

/**
 * Teste sem internet: simula as respostas do Mercado Livre.
 *
 *     php tests/teste_offline.php
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use MercadoLivreEcommerce\Config;
use MercadoLivreEcommerce\Exception\AuthException;
use MercadoLivreEcommerce\Http\HttpClient;
use MercadoLivreEcommerce\Http\Response;
use MercadoLivreEcommerce\MercadoLivre;
use MercadoLivreEcommerce\Token;
use MercadoLivreEcommerce\TokenStorage;
use MercadoLivreEcommerce\Webhook\Notification;

/** Responde com o que estiver na fila e guarda as requisições feitas. */
final class HttpFalso implements HttpClient
{
    /** @var Response[] */
    public array $respostas = [];

    /** @var array<int, array{string, string, array<int, string>, ?string}> */
    public array $feitas = [];

    public function request(string $method, string $url, array $headers = [], ?string $body = null): Response
    {
        $this->feitas[] = [$method, $url, $headers, $body];

        return array_shift($this->respostas) ?? new Response(500, '{}');
    }
}

final class MemoriaStorage implements TokenStorage
{
    public ?Token $token = null;

    public function load(): ?Token
    {
        return $this->token;
    }

    public function save(Token $token): void
    {
        $this->token = $token;
    }
}

$falhas = 0;
function confere(string $descricao, bool $ok): void
{
    global $falhas;
    echo ($ok ? '[ok]    ' : '[FALHA] ') . $descricao . PHP_EOL;
    if (!$ok) {
        $falhas++;
    }
}

$config = new Config('123', 'segredo', 'https://exemplo.com.br/ml_auth.php');
$http = new HttpFalso();
$storage = new MemoriaStorage();
$ml = new MercadoLivre($config, $storage, $http);

// URL de autorização
$url = $ml->auth()->authorizationUrl('abc');
confere('URL de autorização', strpos($url, 'https://auth.mercadolivre.com.br/authorization?response_type=code&client_id=123') === 0
    && strpos($url, 'state=abc') !== false);

// Sem conta conectada
try {
    $ml->users()->me();
    confere('Sem token lança AuthException', false);
} catch (AuthException $e) {
    confere('Sem token lança AuthException', true);
}

// Conectar
$http->respostas[] = new Response(200, '{"access_token":"A1","refresh_token":"R1","expires_in":21600,"user_id":999}');
$token = $ml->connect('TG-code');
confere('connect() salva o token', $storage->token !== null && $storage->token->accessToken() === 'A1' && $token->userId() === 999);
confere('connect() envia grant_type=authorization_code', strpos((string) $http->feitas[0][3], 'grant_type=authorization_code') !== false);

// Token válido: não renova
$http->respostas[] = new Response(200, '{"id":999,"nickname":"LOJA"}');
$eu = $ml->users()->me();
confere('GET com token válido', $eu['nickname'] === 'LOJA' && in_array('Authorization: Bearer A1', $http->feitas[1][2], true));

// Token vencido: renova antes de chamar e salva o refresh token novo
$storage->token = new Token('A1', 'R1', time() - 10, 999);
$http->respostas[] = new Response(200, '{"access_token":"A2","refresh_token":"R2","expires_in":21600,"user_id":999}');
$http->respostas[] = new Response(200, '{"id":999}');
$ml->users()->me();
confere('Token vencido é renovado e salvo', $storage->token->accessToken() === 'A2' && $storage->token->refreshToken() === 'R2');

// 401 inesperado: renova e repete uma vez
$http->feitas = [];
$http->respostas[] = new Response(401, '{"message":"invalid_token"}');
$http->respostas[] = new Response(200, '{"access_token":"A3","refresh_token":"R3","expires_in":21600,"user_id":999}');
$http->respostas[] = new Response(200, '{"id":999}');
$ml->users()->me();
confere('401 renova o token e repete', count($http->feitas) === 3 && $storage->token->accessToken() === 'A3');

// Refresh recusado: precisa reconectar
$storage->token = new Token('A3', 'R3', time() - 10, 999);
$http->respostas[] = new Response(400, '{"error":"invalid_grant","message":"invalid_grant"}');
try {
    $ml->users()->me();
    confere('Refresh recusado lança AuthException', false);
} catch (AuthException $e) {
    confere('Refresh recusado lança AuthException', $e->getError() === 'invalid_grant');
}

// Pedido
$storage->token = new Token('A4', 'R4', time() + 3600, 999);
$http->respostas[] = new Response(200, (string) json_encode([
    'id' => 2000001234,
    'status' => 'paid',
    'pack_id' => null,
    'date_closed' => '2026-10-26T14:00:00.000-03:00',
    'total_amount' => 150.0,
    'paid_amount' => 172.9,
    'buyer' => ['id' => 555, 'nickname' => 'JOAO123'],
    'shipping' => ['id' => 4000001],
    'order_items' => [
        ['item' => ['id' => 'MLB111', 'title' => 'Tinta Acrílica 18L', 'seller_sku' => '1050', 'variation_id' => null], 'quantity' => 1, 'unit_price' => 100.0, 'sale_fee' => 16.5],
        ['item' => ['id' => 'MLB222', 'title' => 'Rolo de pintura', 'seller_sku' => '', 'variation_id' => 777], 'quantity' => 2, 'unit_price' => 25.0, 'sale_fee' => 4.0],
    ],
]));
$pedido = $ml->orders()->get(2000001234);
$itens = $pedido->items();
confere('Pedido: status e totais', $pedido->isPaid() && $pedido->totalAmount() === 150.0 && $pedido->shippingId() === 4000001);
confere('Pedido: itens, SKU e variação', count($itens) === 2 && $itens[0]->sellerSku() === '1050' && $itens[1]->sellerSku() === null && $itens[1]->variationId() === 777);
confere('Pedido: total do item e tarifas', $itens[1]->total() === 50.0 && $pedido->totalSaleFee() === 24.5);

// Erro da API
$http->respostas[] = new Response(404, '{"message":"Order not found","error":"not_found"}');
try {
    $ml->orders()->get(1);
    confere('404 lança ApiException', false);
} catch (\MercadoLivreEcommerce\Exception\ApiException $e) {
    confere('404 lança ApiException', $e->getStatusCode() === 404 && !$e->isTemporary() && !($e instanceof AuthException));
}

/** Query string de uma requisição feita, já separada em campos. */
function query(string $url): array
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $campos);

    return $campos;
}

// Anúncios: paginação de 50 em 50 até o total
$http->feitas = [];
$http->respostas[] = new Response(200, (string) json_encode(['results' => array_map(function ($n) { return 'MLB' . $n; }, range(1, 50)), 'paging' => ['total' => 120]]));
$http->respostas[] = new Response(200, (string) json_encode(['results' => array_map(function ($n) { return 'MLB' . $n; }, range(51, 100)), 'paging' => ['total' => 120]]));
$http->respostas[] = new Response(200, (string) json_encode(['results' => array_map(function ($n) { return 'MLB' . $n; }, range(101, 120)), 'paging' => ['total' => 120]]));
$ids = $ml->items()->idsBySeller(999);
$offsets = array_map(function ($feita) { return query($feita[1])['offset'] ?? null; }, $http->feitas);
confere('Anúncios: busca todas as páginas', count($ids) === 120 && $ids[119] === 'MLB120' && $offsets === ['0', '50', '100']);
confere('Anúncios: vendedor, status e limite na busca', strpos($http->feitas[0][1], '/users/999/items/search?') !== false
    && query($http->feitas[0][1])['status'] === 'active' && query($http->feitas[0][1])['limit'] === '50');

// Anúncios: página vazia encerra mesmo com total maior
$http->feitas = [];
$http->respostas[] = new Response(200, '{"results":[],"paging":{"total":300}}');
confere('Anúncios: página vazia encerra a busca', $ml->items()->idsBySeller(999, 'paused') === [] && count($http->feitas) === 1
    && query($http->feitas[0][1])['status'] === 'paused');

// Anúncios: detalhes em lotes de 20, ignorando os que não vieram
$http->feitas = [];
$lista = array_map(function ($n) { return 'MLB' . $n; }, range(1, 45));
foreach ([array_slice($lista, 0, 20), array_slice($lista, 20, 20), array_slice($lista, 40)] as $lote) {
    $http->respostas[] = new Response(200, (string) json_encode(array_map(function ($id) {
        return $id === 'MLB7'
            ? ['code' => 404, 'body' => ['message' => 'Item not found']]
            : ['code' => 200, 'body' => ['id' => $id, 'title' => 'Anúncio ' . $id, 'seller_sku' => null]];
    }, $lote)));
}
$anuncios = $ml->items()->getMany($lista);
$lotes = array_map(function ($feita) { return count(explode(',', query($feita[1])['ids'])); }, $http->feitas);
confere('Anúncios: getMany em lotes de 20', $lotes === [20, 20, 5]);
confere('Anúncios: getMany ignora anúncio não encontrado', count($anuncios) === 44 && $anuncios[6]['id'] === 'MLB8');

$http->feitas = [];
confere('Anúncios: getMany sem ids não chama a API', $ml->items()->getMany([]) === [] && $http->feitas === []);

// Envio: endereço de entrega com o formato novo
$http->feitas = [];
$http->respostas[] = new Response(200, '{"id":4000001,"status":"ready_to_ship","receiver_address":{"zip_code":"30140071","city":{"name":"Belo Horizonte"},"state":{"id":"BR-MG"}}}');
$envio = $ml->shipments()->get(4000001);
confere('Envio: busca pelo id com x-format-new', strpos($http->feitas[0][1], '/shipments/4000001') !== false
    && in_array('x-format-new: true', $http->feitas[0][2], true));
confere('Envio: devolve o endereço', $envio['receiver_address']['city']['name'] === 'Belo Horizonte');

// Faturamento do comprador
$http->feitas = [];
$http->respostas[] = new Response(200, '{"billing_info":{"doc_type":"CPF","doc_number":"00996900632"}}');
$faturamento = $ml->orders()->billingInfo(2000001234);
confere('Faturamento: endpoint e documento', strpos($http->feitas[0][1], '/orders/2000001234/billing_info') !== false
    && $faturamento['billing_info']['doc_number'] === '00996900632');

// Rede de segurança: pedidos pagos recentes
$http->feitas = [];
$http->respostas[] = new Response(200, (string) json_encode(['results' => [
    ['id' => 2000000002, 'status' => 'paid', 'order_items' => []],
    ['id' => 2000000001, 'status' => 'paid', 'order_items' => []],
], 'paging' => ['total' => 2]]));
$recentes = $ml->orders()->recentPaid(999, 10);
$filtros = query($http->feitas[0][1]);
confere('Pagos recentes: filtros da busca', strpos($http->feitas[0][1], '/orders/search?') !== false && $filtros['seller'] === '999'
    && strpos($http->feitas[0][1], 'order.status=paid') !== false && $filtros['sort'] === 'date_desc' && $filtros['limit'] === '10');
confere('Pagos recentes: vira lista de Order', count($recentes) === 2 && $recentes[0]->id() === 2000000002 && $recentes[1]->isPaid());

$http->respostas[] = new Response(200, '{"results":[],"paging":{"total":0}}');
confere('Pagos recentes: nenhum pedido', $ml->orders()->recentPaid(999) === []);

// Webhook
$aviso = Notification::fromJson('{"resource":"/orders/2000001234","user_id":999,"topic":"orders_v2","application_id":123,"attempts":1}');
confere('Webhook: pedido da conta certa', $aviso->isOrder() && $aviso->belongsTo(999, '123') && $aviso->resourceId() === '2000001234');
confere('Webhook: outra conta é recusada', !$aviso->belongsTo(111));
confere('Webhook: resource estranho não vira id', Notification::fromJson('{"resource":"/orders/1;drop","topic":"orders_v2"}')->resourceId() === null);
try {
    Notification::fromJson('lixo');
    confere('Webhook: corpo inválido é recusado', false);
} catch (\InvalidArgumentException $e) {
    confere('Webhook: corpo inválido é recusado', true);
}

echo PHP_EOL . ($falhas === 0 ? 'Tudo certo.' : "$falhas falha(s).") . PHP_EOL;
exit($falhas === 0 ? 0 : 1);
