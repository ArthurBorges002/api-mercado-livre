<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Http;

use ApiMercadoLivre\Exception\ApiException;

final class CurlHttpClient implements HttpClient
{
    private int $timeout;
    private ?string $caBundle;

    /**
     * @param int         $timeout  segundos até desistir da requisição
     * @param string|null $caBundle caminho de um cacert.pem, para servidores
     *                              (comum no Windows) sem certificados raiz configurados
     */
    public function __construct(int $timeout = 20, ?string $caBundle = null)
    {
        $this->timeout = $timeout;
        $this->caBundle = $caBundle;
    }

    public function request(string $method, string $url, array $headers = [], ?string $body = null): Response
    {
        $ch = curl_init($url);

        $opcoes = [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $opcoes[CURLOPT_POSTFIELDS] = $body;
        }
        if ($this->caBundle !== null) {
            $opcoes[CURLOPT_CAINFO] = $this->caBundle;
        }
        curl_setopt_array($ch, $opcoes);

        $resposta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        // Sem curl_close(): o handle é liberado ao sair da função, e no PHP 8.5 a função gera aviso de obsoleta.

        if ($resposta === false) {
            throw new ApiException('Falha de comunicação com o Mercado Livre: ' . $erro);
        }

        return new Response($status, (string) $resposta);
    }
}
