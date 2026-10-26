<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Http;

final class Response
{
    private int $status;
    private string $body;

    public function __construct(int $status, string $body)
    {
        $this->status = $status;
        $this->body = $body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Corpo decodificado. Retorna [] quando vazio ou quando não é JSON.
     *
     * @return array<mixed>
     */
    public function json(): array
    {
        $dados = json_decode($this->body, true);

        return is_array($dados) ? $dados : [];
    }

    public function isSuccess(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
