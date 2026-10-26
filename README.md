# api-mercado-livre

Biblioteca PHP para integrar sistemas de gestão (ERP) com o **Mercado Livre**.

Ela cuida da parte do Mercado Livre: autorização da conta, renovação automática do token, leitura dos avisos de venda (webhook) e busca de pedidos, envios e anúncios. Ela **não conhece o banco do seu sistema**: quem decide como gravar o pedido é o ERP.

- PHP **7.4 ou superior** (testada no 7.4 e no 8)
- Extensões `curl` e `json`
- Sem dependências externas: funciona com ou sem Composer

## Instalação

**Sem Composer** (copiando a pasta para dentro do sistema):

```php
require __DIR__ . '/api-mercado-livre/autoload.php';
```

**Com Composer:**

```bash
composer require arthurborges002/api-mercado-livre
```

## Antes de usar

1. Crie um aplicativo em [developers.mercadolivre.com.br](https://developers.mercadolivre.com.br).
2. Cadastre a **Redirect URI** (a página que conclui a autorização) e a **URL de notificações** (o webhook). As duas precisam ser HTTPS.
3. Marque o tópico de notificações **Orders** (`orders_v2`).
4. Anote o **App ID** e a **Secret Key**. A Secret Key é uma senha: nunca coloque no Git.

## Uso

### 1. Configurar

```php
use MercadoLivreEcommerce\Config;
use MercadoLivreEcommerce\MercadoLivre;

$config = new Config($clientId, $clientSecret, 'https://seu-dominio/ml_auth.php');
$ml = new MercadoLivre($config, $storage);
```

`$storage` é onde os tokens ficam guardados. Implemente a interface `TokenStorage` com o banco do seu sistema:

```php
use MercadoLivreEcommerce\Token;
use MercadoLivreEcommerce\TokenStorage;

final class MeuStorage implements TokenStorage
{
    public function load(): ?Token
    {
        // SELECT access_token, refresh_token, expires_at, user_id FROM ...
        // return $linha ? Token::fromArray($linha) : null;
    }

    public function save(Token $token): void
    {
        // UPDATE ... com $token->toArray()
    }
}
```

Para testes existe o `Storage\FileTokenStorage`, que grava num arquivo JSON (deixe o arquivo fora da pasta pública).

### 2. Conectar a conta (uma vez)

```php
// Botão "Conectar": manda o usuário para o Mercado Livre
$_SESSION['ml_state'] = bin2hex(random_bytes(16));
header('Location: ' . $ml->auth()->authorizationUrl($_SESSION['ml_state']));

// Na Redirect URI: confere o state e troca o code pelos tokens (já salva no storage)
$token = $ml->connect($_GET['code']);
```

Exemplo completo em [`examples/autorizar.php`](examples/autorizar.php).

### 3. Receber avisos de venda

```php
use MercadoLivreEcommerce\Webhook\Notification;

$aviso = Notification::fromJson(file_get_contents('php://input'));
if ($aviso->isOrder() && $aviso->belongsTo($sellerId)) {
    // guarde $aviso->resourceId() numa fila
}
http_response_code(200); // responda rápido; processe a fila em outro script
```

Qualquer um pode chamar a URL do webhook. Use o aviso só para saber **qual** pedido buscar e sempre busque o pedido verdadeiro na API.

### 4. Buscar o pedido

```php
$pedido = $ml->orders()->get($orderId);

if ($pedido->isPaid()) {
    foreach ($pedido->items() as $item) {
        $item->itemId();      // MLB1234567890
        $item->variationId(); // null ou id da variação
        $item->sellerSku();   // SKU do anúncio, se preenchido
        $item->quantity();
        $item->unitPrice();
    }
}
```

Outros recursos:

| Chamada | Retorna |
| --- | --- |
| `$ml->orders()->recentPaid($sellerId)` | Pedidos pagos recentes (para recuperar avisos perdidos) |
| `$ml->orders()->billingInfo($orderId)` | Dados de faturamento do comprador (CPF/CNPJ) |
| `$ml->shipments()->get($pedido->shippingId())` | Envio e endereço de entrega |
| `$ml->items()->idsBySeller($sellerId)` | Ids dos anúncios ativos |
| `$ml->items()->getMany($ids)` | Detalhes dos anúncios (título, SKU, variações) |
| `$ml->users()->me()` | Conta conectada |
| `$ml->get('/qualquer/recurso')` | Qualquer outro endpoint da API |

### Token

Toda chamada confere o token e o renova sozinha quando está vencendo. Se o Mercado Livre recusar a renovação (senha trocada, app revogado), é lançada uma `AuthException` e a conta precisa ser conectada de novo.

### Erros

| Exceção | Quando |
| --- | --- |
| `Exception\AuthException` | Conta não conectada ou autorização perdida |
| `Exception\ApiException` | A API respondeu com erro; veja `getStatusCode()`, `getError()` e `isTemporary()` |
| `Exception\MercadoLivreException` | Base das duas: capture esta para tratar qualquer erro |

## Testes

```bash
php tests/teste_offline.php
```

Roda sem internet, simulando as respostas do Mercado Livre.

## Licença

[MIT](LICENSE)
