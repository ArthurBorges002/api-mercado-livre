<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Http;

/**
 * Faz as requisições HTTP. Existe como interface para poder trocar nos testes.
 */
interface HttpClient
{
    /**
     * Deve retornar a resposta mesmo com status de erro (4xx, 5xx).
     * Deve lançar exceção só quando não houver resposta (rede, DNS, timeout).
     *
     * @param array<int, string> $headers ex.: ['Accept: application/json']
     */
    public function request(string $method, string $url, array $headers = [], ?string $body = null): Response;
}
