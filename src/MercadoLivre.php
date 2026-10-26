<?php

declare(strict_types=1);

namespace ApiMercadoLivre;

use ApiMercadoLivre\Exception\ApiException;
use ApiMercadoLivre\Exception\AuthException;
use ApiMercadoLivre\Http\CurlHttpClient;
use ApiMercadoLivre\Http\HttpClient;
use ApiMercadoLivre\Resource\Items;
use ApiMercadoLivre\Resource\Orders;
use ApiMercadoLivre\Resource\Shipments;
use ApiMercadoLivre\Resource\Users;

/**
 * Ponto de entrada da biblioteca.
 *
 *     $ml = new MercadoLivre($config, $storage);
 *     $pedido = $ml->orders()->get(2000001234);
 *
 * Toda chamada usa o token salvo no TokenStorage e o renova sozinha quando vence.
 */
final class MercadoLivre
{
    private TokenStorage $storage;
    private HttpClient $http;
    private Auth $auth;

    public function __construct(Config $config, TokenStorage $storage, ?HttpClient $http = null)
    {
        $this->storage = $storage;
        $this->http = $http ?? new CurlHttpClient();
        $this->auth = new Auth($config, $this->http);
    }

    public function auth(): Auth
    {
        return $this->auth;
    }

    public function orders(): Orders
    {
        return new Orders($this);
    }

    public function shipments(): Shipments
    {
        return new Shipments($this);
    }

    public function items(): Items
    {
        return new Items($this);
    }

    public function users(): Users
    {
        return new Users($this);
    }

    /**
     * Troca o code do retorno da autorização pelos tokens e já salva.
     */
    public function connect(string $code): Token
    {
        $token = $this->auth->exchangeCode($code);
        $this->storage->save($token);

        return $token;
    }

    /**
     * Token válido da conta, renovado se estiver vencendo.
     *
     * @throws AuthException quando a conta precisa ser autorizada de novo
     */
    public function token(bool $forcarRenovacao = false): Token
    {
        $token = $this->storage->load();
        if ($token === null) {
            throw new AuthException('Conta do Mercado Livre não conectada.');
        }
        if (!$forcarRenovacao && !$token->isExpired()) {
            return $token;
        }

        try {
            $novo = $this->auth->refresh($token);
        } catch (AuthException $e) {
            // O refresh token é de uso único. Se outro processo renovou primeiro,
            // o nosso já não vale, mas o token novo está salvo: usa ele.
            $salvo = $this->storage->load();
            if ($salvo !== null && $salvo->refreshToken() !== $token->refreshToken() && !$salvo->isExpired()) {
                return $salvo;
            }
            throw $e;
        }

        $this->storage->save($novo);

        return $novo;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<int, string>   $headers
     * @return array<mixed>
     */
    public function get(string $caminho, array $query = [], array $headers = []): array
    {
        return $this->request('GET', $caminho, $query, null, $headers);
    }

    /**
     * @param array<mixed> $corpo
     * @return array<mixed>
     */
    public function post(string $caminho, array $corpo = []): array
    {
        return $this->request('POST', $caminho, [], $corpo);
    }

    /**
     * @param array<mixed> $corpo
     * @return array<mixed>
     */
    public function put(string $caminho, array $corpo = []): array
    {
        return $this->request('PUT', $caminho, [], $corpo);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<mixed>|null    $corpo
     * @param array<int, string>   $headers
     * @return array<mixed>
     */
    private function request(string $metodo, string $caminho, array $query, ?array $corpo, array $headers = []): array
    {
        $url = Config::API_URL . '/' . ltrim($caminho, '/');
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }
        $json = $corpo === null ? null : (string) json_encode($corpo);

        $resposta = $this->enviar($metodo, $url, $json, $headers, $this->token());

        // Token recusado antes do prazo (revogado ou renovado em outro lugar): renova e tenta uma vez.
        if ($resposta->status() === 401) {
            $resposta = $this->enviar($metodo, $url, $json, $headers, $this->token(true));
        }

        $dados = $resposta->json();
        if (!$resposta->isSuccess()) {
            $mensagem = isset($dados['message']) ? (string) $dados['message'] : 'HTTP ' . $resposta->status();
            $classe = $resposta->status() === 401 ?AuthException::class : ApiException::class;
            throw new $classe("Erro em $metodo /" . ltrim($caminho, '/') . ': ' . $mensagem, $resposta->status(), $dados);
        }

        return $dados;
    }

    /**
     * @param array<int, string> $headers
     */
    private function enviar(string $metodo, string $url, ?string $json, array $headers, Token $token): Http\Response
    {
        $headers[] = 'Authorization: Bearer ' . $token->accessToken();
        $headers[] = 'Accept: application/json';
        if ($json !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        return $this->http->request($metodo, $url, $headers, $json);
    }
}
