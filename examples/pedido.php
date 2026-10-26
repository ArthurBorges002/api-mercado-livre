<?php

/**
 * Mostrar um pedido no terminal:
 *
 *     php examples/pedido.php 2000001234
 */

declare(strict_types=1);

require __DIR__ . '/_config.php';

$orderId = $argv[1] ?? null;
if ($orderId === null) {
    exit("Uso: php examples/pedido.php ID_DO_PEDIDO\n");
}

$pedido = $ml->orders()->get($orderId);

echo "Pedido {$pedido->id()} - {$pedido->status()} - {$pedido->dateClosed()}\n";
echo "Comprador: {$pedido->buyerNickname()}\n";
foreach ($pedido->items() as $item) {
    printf(
        "  %s%s | SKU %s | %s x %.2f = %.2f\n",
        $item->itemId(),
        $item->variationId() ? ' (var. ' . $item->variationId() . ')' : '',
        $item->sellerSku() ?? '-',
        $item->quantity(),
        $item->unitPrice(),
        $item->total()
    );
}
printf("Total: %.2f | Tarifas ML: %.2f\n", $pedido->totalAmount(), $pedido->totalSaleFee());
