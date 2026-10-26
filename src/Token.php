<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce;

/**
 * Tokens de acesso de uma conta do Mercado Livre.
 *
 * O access token vale poucas horas. O refresh token é de uso único: cada
 * renovação devolve um novo, que precisa ser salvo no lugar do antigo.
 */
final class Token
{
    /** Renova um pouco antes de vencer, para não falhar no meio de uma chamada. */
    public const MARGEM_SEGUNDOS = 300;

    private string $accessToken;
    private string $refreshToken;
    private int $expiresAt;
    private int $userId;

    public function __construct(string $accessToken, string $refreshToken, int $expiresAt, int $userId)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresAt = $expiresAt;
        $this->userId = $userId;
    }

    /**
     * Monta o token a partir da resposta de /oauth/token.
     *
     * @param array<string, mixed> $resposta
     */
    public static function fromOAuthResponse(array $resposta, ?int $agora = null): self
    {
        if (empty($resposta['access_token']) || empty($resposta['refresh_token'])) {
            throw new \InvalidArgumentException('Resposta de autorização sem access_token ou refresh_token.');
        }

        $agora = $agora ?? time();

        return new self(
            (string) $resposta['access_token'],
            (string) $resposta['refresh_token'],
            $agora + (int) ($resposta['expires_in'] ?? 0),
            (int) ($resposta['user_id'] ?? 0)
        );
    }

    /**
     * @param array<string, mixed> $dados formato de toArray()
     */
    public static function fromArray(array $dados): self
    {
        return new self(
            (string) $dados['access_token'],
            (string) $dados['refresh_token'],
            (int) $dados['expires_at'],
            (int) $dados['user_id']
        );
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_at: int, user_id: int}
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt,
            'user_id' => $this->userId,
        ];
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    /** Timestamp Unix em que o access token vence. */
    public function expiresAt(): int
    {
        return $this->expiresAt;
    }

    /** Id da conta (vendedor) no Mercado Livre. */
    public function userId(): int
    {
        return $this->userId;
    }

    public function isExpired(?int $agora = null): bool
    {
        return ($agora ?? time()) >= $this->expiresAt - self::MARGEM_SEGUNDOS;
    }
}
