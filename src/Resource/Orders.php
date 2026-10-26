<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Resource;

use MercadoLivreEcommerce\MercadoLivre;
use MercadoLivreEcommerce\Model\Order;

final class Orders
{
    private MercadoLivre $ml;

    public function __construct(MercadoLivre $ml)
    {
        $this->ml = $ml;
    }

    /**
     * @param int|string $orderId
     */
    public function get($orderId): Order
    {
        return Order::fromArray($this->ml->get('/orders/' . $orderId));
    }

    /**
     * Busca pedidos do vendedor. Filtros comuns: order.status, sort, offset, limit.
     *
     * @param array<string, mixed> $filtros
     * @return array<mixed> resposta crua: results, paging...
     */
    public function search(int $sellerId, array $filtros = []): array
    {
        return $this->ml->get('/orders/search', ['seller' => $sellerId] + $filtros);
    }

    /**
     * Pedidos pagos mais recentes. Use como rede de segurança para avisos perdidos.
     *
     * @return Order[]
     */
    public function recentPaid(int $sellerId, int $limite = 50): array
    {
        $resposta = $this->search($sellerId, [
            'order.status' => 'paid',
            'sort' => 'date_desc',
            'limit' => $limite,
        ]);

        return array_map([Order::class, 'fromArray'], $resposta['results'] ?? []);
    }

    /**
     * Dados de faturamento do comprador (nome, CPF/CNPJ, endereço fiscal).
     *
     * Atenção: o Mercado Livre está migrando este recurso para um endpoint novo.
     * Confira na documentação oficial antes de usar em produção.
     *
     * @param int|string $orderId
     * @return array<mixed>
     */
    public function billingInfo($orderId): array
    {
        return $this->ml->get('/orders/' . $orderId . '/billing_info');
    }
}
