<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Resource;

use ApiMercadoLivre\MercadoLivre;
use ApiMercadoLivre\Model\Shipment;

final class Shipments
{
    private MercadoLivre $ml;

    public function __construct(MercadoLivre $ml)
    {
        $this->ml = $ml;
    }

    /**
     * Envio cru, como veio da API (formato novo): endereço de entrega em
     * destination.shipping_address, destinatário em destination, modalidade em
     * logistic.type. Para os campos já arrumados, use find().
     * O id vem no pedido (Order::shippingId()).
     *
     * @param int|string $shipmentId
     * @return array<mixed>
     */
    public function get($shipmentId): array
    {
        return $this->ml->get('/shipments/' . $shipmentId, [], ['x-format-new: true']);
    }

    /**
     * Envio com endereço, destinatário e modalidade já lidos (formato novo ou antigo).
     *
     * @param int|string $shipmentId
     */
    public function find($shipmentId): Shipment
    {
        return Shipment::fromArray($this->get($shipmentId));
    }
}
