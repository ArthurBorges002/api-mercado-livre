<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce;

/**
 * Onde o sistema guarda os tokens da conta (banco de dados, arquivo...).
 *
 * Cada sistema implementa a sua. Exemplo no ERP: ler e gravar na tabela ml_config.
 */
interface TokenStorage
{
    /** Retorna o token salvo, ou null se a conta ainda não foi autorizada. */
    public function load(): ?Token;

    public function save(Token $token): void;
}
