<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce;

/**
 * Credenciais do aplicativo criado no portal de desenvolvedores do Mercado Livre.
 */
final class Config
{
    public const API_URL = 'https://api.mercadolibre.com';
    public const AUTH_URL_BRASIL = 'https://auth.mercadolivre.com.br';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $authUrl;

    /**
     * @param string $clientId     App ID do aplicativo
     * @param string $clientSecret Secret Key do aplicativo (nunca versionar)
     * @param string $redirectUri  URL de retorno cadastrada no aplicativo (HTTPS)
     * @param string $authUrl      Domínio de autorização do país da conta
     */
    public function __construct(string $clientId, string $clientSecret, string $redirectUri, string $authUrl = self::AUTH_URL_BRASIL)
    {
        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            throw new \InvalidArgumentException('Client ID, Client Secret e Redirect URI são obrigatórios.');
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->authUrl = rtrim($authUrl, '/');
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): string
    {
        return $this->clientSecret;
    }

    public function redirectUri(): string
    {
        return $this->redirectUri;
    }

    public function authUrl(): string
    {
        return $this->authUrl;
    }
}
