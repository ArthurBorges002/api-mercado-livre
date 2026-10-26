<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Model;

/**
 * Um item (anúncio + quantidade) de um pedido.
 */
final class OrderItem
{
    private string $itemId;
    private ?int $variationId;
    private ?string $sellerSku;
    private string $title;
    private float $quantity;
    private float $unitPrice;
    private float $saleFee;

    public function __construct(string $itemId, ?int $variationId, ?string $sellerSku, string $title, float $quantity, float $unitPrice, float $saleFee)
    {
        $this->itemId = $itemId;
        $this->variationId = $variationId;
        $this->sellerSku = $sellerSku;
        $this->title = $title;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->saleFee = $saleFee;
    }

    /**
     * @param array<mixed> $dados um elemento de order_items
     */
    public static function fromArray(array $dados): self
    {
        $item = $dados['item'] ?? [];
        $sku = $item['seller_sku'] ?? $item['seller_custom_field'] ?? null;

        return new self(
            (string) ($item['id'] ?? ''),
            empty($item['variation_id']) ? null : (int) $item['variation_id'],
            $sku === null || $sku === '' ? null : (string) $sku,
            (string) ($item['title'] ?? ''),
            (float) ($dados['quantity'] ?? 0),
            (float) ($dados['unit_price'] ?? 0),
            (float) ($dados['sale_fee'] ?? 0)
        );
    }

    /** Id do anúncio, ex.: MLB1234567890 */
    public function itemId(): string
    {
        return $this->itemId;
    }

    /** Id da variação (cor, tamanho...), ou null se o anúncio não tem variação. */
    public function variationId(): ?int
    {
        return $this->variationId;
    }

    /** SKU informado pelo vendedor no anúncio, se houver. */
    public function sellerSku(): ?string
    {
        return $this->sellerSku;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    /** Preço unitário vendido. */
    public function unitPrice(): float
    {
        return $this->unitPrice;
    }

    /** Tarifa de venda cobrada pelo Mercado Livre, por unidade. */
    public function saleFee(): float
    {
        return $this->saleFee;
    }

    public function total(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }
}
