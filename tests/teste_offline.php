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
