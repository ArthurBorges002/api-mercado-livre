<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Storage;

use MercadoLivreEcommerce\Token;
use MercadoLivreEcommerce\TokenStorage;

/**
 * Guarda o token num arquivo JSON. Útil para testes e exemplos.
 *
 * O arquivo contém credenciais: deixe-o FORA da pasta pública do site.
 */
final class FileTokenStorage implements TokenStorage
{
    private string $arquivo;

    public function __construct(string $arquivo)
    {
        $this->arquivo = $arquivo;
    }

    public function load(): ?Token
    {
        if (!is_file($this->arquivo)) {
            return null;
        }

        $dados = json_decode((string) file_get_contents($this->arquivo), true);

        return is_array($dados) ? Token::fromArray($dados) : null;
    }

    public function save(Token $token): void
    {
        $gravou = file_put_contents($this->arquivo, json_encode($token->toArray()), LOCK_EX);
        if ($gravou === false) {
            throw new \RuntimeException('Não foi possível gravar o token em ' . $this->arquivo);
        }
    }
}
