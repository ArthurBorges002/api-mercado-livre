<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Model;

/**
 * Pedido do Mercado Livre, com os campos que um ERP costuma precisar.
 * O pedido completo, como veio da API, fica em raw().
 */
final class Order
{
    public const STATUS_PAGO = 'paid';
    public const STATUS_CANCELADO = 'cancelled';

    /** @var array<mixed> */
    private array $raw;

    /** @var OrderItem[] */
    private array $items;

    /**
     * @param array<mixed> $raw
     */
    private function __construct(array $raw)
    {
        $this->raw = $raw;
        $this->items = array_map([OrderItem::class, 'fromArray'], $raw['order_items'] ?? []);
    }

    /**
     * @param array<mixed> $dados resposta de GET /orders/{id}
     */
    public static function fromArray(array $dados): self
    {
        if (empty($dados['id'])) {
            throw new \InvalidArgumentException('Pedido sem id.');
        }

        return new self($dados);
    }

    public function id(): int
    {
        return (int) $this->raw['id'];
    }

    /** paid, cancelled, confirmed, payment_required... */
    public function status(): string
    {
        return (string) ($this->raw['status'] ?? '');
    }

    public function isPaid(): bool
    {
        return $this->status() === self::STATUS_PAGO;
    }

    public function isCancelled(): bool
    {
        return $this->status() === self::STATUS_CANCELADO;
    }

    /** Carrinho: pedidos comprados juntos têm o mesmo pack_id. */
    public function packId(): ?int
    {
        return empty($this->raw['pack_id']) ? null : (int) $this->raw['pack_id'];
    }

    /** Data em que o pedido foi fechado (ISO 8601), ex.: 2026-10-26T14:00:00.000-03:00 */
    public function dateClosed(): ?string
    {
        $data = $this->raw['date_closed'] ?? $this->raw['date_created'] ?? null;

        return $data === null ? null : (string) $data;
    }

    /** Soma dos itens, sem frete. */
    public function totalAmount(): float
    {
        return (float) ($this->raw['total_amount'] ?? 0);
    }

    /** Valor pago pelo comprador (pode incluir frete). */
    public function paidAmount(): float
    {
        return (float) ($this->raw['paid_amount'] ?? 0);
    }

    public function buyerId(): ?int
    {
        return empty($this->raw['buyer']['id']) ? null : (int) $this->raw['buyer']['id'];
    }

    public function buyerNickname(): string
    {
        return (string) ($this->raw['buyer']['nickname'] ?? '');
    }

    /** Id dos dados de faturamento (buyer.billing_info.id), usado em Orders::billingInfo(). */
    public function billingInfoId(): ?string
    {
        return empty($this->raw['buyer']['billing_info']['id']) ? null : (string) $this->raw['buyer']['billing_info']['id'];
    }

    public function shippingId(): ?int
    {
        return empty($this->raw['shipping']['id']) ? null : (int) $this->raw['shipping']['id'];
    }

    /**
     * @return OrderItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    /** Soma das tarifas de venda do Mercado Livre. */
    public function totalSaleFee(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->saleFee() * $item->quantity();
        }

        return round($total, 2);
    }

    /**
     * Pedido completo como veio da API, para guardar ou consultar campos extras.
     *
     * @return array<mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
