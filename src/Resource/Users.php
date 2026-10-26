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
}
