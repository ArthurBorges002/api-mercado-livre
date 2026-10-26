<?php

declare(strict_types=1);

namespace ApiMercadoLivre;

use ApiMercadoLivre\Exception\AuthException;
use ApiMercadoLivre\Http\HttpClient;

/**
 * Fluxo OAuth 2.0 do Mercado Livre.
 *
 * 1. Mande o usuário para authorizationUrl().
 * 2. O Mercado Livre volta para a redirect URI com ?code=...&state=...
 * 3. Troque o code por tokens com exchangeCode() e salve.
 * 4. Quando o access token vencer, use refresh() (a classe MercadoLivre faz isso sozinha).
 */
final class Auth
{
    private Config $config;
    private HttpClient $http;

    public function __construct(Config $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http = $http;
    }

    /**
     * URL da tela do Mercado Livre onde o dono da conta autoriza o aplicativo.
     *
     * @param string $state valor aleatório guardado na sessão e conferido no retorno,
     *                      para garantir que o retorno veio de um pedido seu
     */
    public function authorizationUrl(string $state): string
    {
        return $this->config->authUrl() . '/authorization?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config->clientId(),
            'redirect_uri' => $this->config->redirectUri(),
            'state' => $state,
        ]);
    }

    /** Troca o code recebido na redirect URI pelos tokens da conta. */
    public function exchangeCode(string $code): Token
    {
        return $this->pedirToken([
            'grant_type' => 'authorization_code',
            'client_id' => $this->config->clientId(),
            'client_secret' => $this->config->clientSecret(),
            'code' => $code,
            'redirect_uri' => $this->config->redirectUri(),
        ]);
    }

    /** Gera um novo access token. O refresh token antigo deixa de valer. */
    public function refresh(Token $token): Token
    {
        return $this->pedirToken([
            'grant_type' => 'refresh_token',
            'client_id' => $this->config->clientId(),
            'client_secret' => $this->config->clientSecret(),
            'refresh_token' => $token->refreshToken(),
        ]);
    }

    /**
     * @param array<string, string> $campos
     */
    private function pedirToken(array $campos): Token
    {
        $resposta = $this->http->request(
            'POST',
            Config::API_URL . '/oauth/token',
            ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            http_build_query($campos)
        );
        $dados = $resposta->json();

        if (!$resposta->isSuccess() || empty($dados['access_token'])) {
            $detalhe = isset($dados['message']) ? (string) $dados['message'] : $resposta->body();
            throw new AuthException('Falha na autorização do Mercado Livre: ' . $detalhe, $resposta->status(), $dados);
        }

        return Token::fromOAuthResponse($dados);
    }
}
