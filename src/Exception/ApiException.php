<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Exception;

/**
 * A API do Mercado Livre respondeu com erro (HTTP 4xx ou 5xx).
 */
class ApiException extends MercadoLivreException
{
    private int $statusCode;

    /** @var array<string, mixed> */
    private array $body;

    /**
     * @param array<string, mixed> $body
     */
    public function __construct(string $message, int $statusCode = 0, array $body = [])
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Corpo da resposta de erro, como veio da API.
     *
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Código do erro informado pela API (ex.: "invalid_grant", "not_found").
     */
    public function getError(): ?string
    {
        return isset($this->body['error']) ? (string) $this->body['error'] : null;
    }

    /**
     * Limite de requisições atingido: vale tentar de novo mais tarde.
     */
    public function isRateLimit(): bool
    {
        return $this->statusCode === 429;
    }

    /**
     * Erro do lado do Mercado Livre ou de rede: vale tentar de novo mais tarde.
     */
    public function isTemporary(): bool
    {
        return $this->statusCode === 0 || $this->statusCode === 429 || $this->statusCode >= 500;
    }
}
