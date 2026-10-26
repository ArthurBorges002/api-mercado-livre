<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Resource;

use MercadoLivreEcommerce\MercadoLivre;

final class Shipments
{
    private MercadoLivre $ml;

    public function __construct(MercadoLivre $ml)
    {
        $this->ml = $ml;
    }

    /**
     * Dados do envio: endereço de entrega (receiver_address), modalidade, status.
     * O id vem no pedido (Order::shippingId()).
     *
     * @param int|string $shipmentId
     * @return array<mixed>
     */
    public function get($shipmentId): array
    {
        return $this->ml->get('/shipments/' . $shipmentId, [], ['x-format-new: true']);
    }
}
