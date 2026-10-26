<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Resource;

use MercadoLivreEcommerce\MercadoLivre;

final class Users
{
    private MercadoLivre $ml;

    public function __construct(MercadoLivre $ml)
    {
        $this->ml = $ml;
    }

    /**
     * Conta conectada (id, nickname...). Bom para testar se a autorização funcionou.
     *
     * @return array<mixed>
     */
    public function me(): array
    {
        return $this->ml->get('/users/me');
    }

    /**
     * Cria um usuário de teste (vendedor ou comprador) no site informado.
     * A resposta traz id, nickname, password e site_status; guarde login e senha.
     * Vendas entre usuários de teste não cobram nada.
     *
     * @return array<mixed>
     */
    public function createTestUser(string $siteId = 'MLB'): array
    {
        return $this->ml->post('/users/test_user', ['site_id' => $siteId]);
    }
}
