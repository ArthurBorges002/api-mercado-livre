<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Resource;

use ApiMercadoLivre\MercadoLivre;
use ApiMercadoLivre\Model\Order;

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
     * Passe $desde (por exemplo, a data em que a conta foi conectada) para não
     * trazer vendas antigas, feitas antes da integração existir.
     *
     * @return Order[]
     */
    public function recentPaid(int $sellerId, int $limite = 50, ?\DateTimeInterface $desde = null): array
    {
        $filtros = [
            'order.status' => 'paid',
            'sort' => 'date_desc',
            'limit' => $limite,
        ];
        if ($desde !== null) {
            $filtros['order.date_created.from'] = $desde->format('Y-m-d\TH:i:s.vP');
        }

        $resposta = $this->search($sellerId, $filtros);
        $pedidos = array_map([Order::class, 'fromArray'], $resposta['results'] ?? []);

        if ($desde === null) {
            return $pedidos;
        }

        // O ML arredonda order.date_created.from para a hora cheia (testado em 09/11/2026):
        // descarta aqui o que foi criado antes de $desde.
        return array_values(array_filter($pedidos, static function (Order $pedido) use ($desde): bool {
            $criado = $pedido->raw()['date_created'] ?? null;

            return !is_string($criado) || strtotime($criado) >= $desde->getTimestamp();
        }));
    }

    /**
     * Dados de faturamento do comprador: buyer.billing_info com name, last_name,
     * identification (type CPF/CNPJ, number sem máscara), address e taxes.
     *
     * O id vem do pedido: Order::billingInfoId(). O endereço é o fiscal e pode ser
     * diferente do endereço de entrega (Shipments::get()). Campos podem vir ausentes
     * ou nulos, então trate cada um como opcional.
     *
     * Substitui o antigo /orders/{id}/billing_info, que o Mercado Livre descontinuou.
     *
     * @return array<mixed>
     */
    public function billingInfo(string $billingInfoId, string $siteId = 'MLB'): array
    {
        return $this->ml->get('/orders/billing-info/' . rawurlencode($siteId) . '/' . rawurlencode($billingInfoId));
    }
}
