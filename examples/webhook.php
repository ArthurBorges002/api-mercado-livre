<?php

/**
 * Receber os avisos do Mercado Livre.
 *
 * Regra de ouro: só anotar e responder 200 rápido. Buscar o pedido e gravar no
 * sistema é trabalho de outro script (cron), que lê a fila.
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use ApiMercadoLivre\Webhook\Notification;

$sellerId = (int) getenv('ML_SELLER_ID');
$fila = getenv('ML_FILA_FILE') ?: sys_get_temp_dir() . '/ml_fila.txt';

try {
    $aviso = Notification::fromJson((string) file_get_contents('php://input'));

    if ($aviso->isOrder() && $aviso->belongsTo($sellerId) && $aviso->resourceId() !== null) {
        // No ERP, aqui seria um INSERT na tabela de fila (ml_notificacao).
        file_put_contents($fila, $aviso->resourceId() . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
} catch (\InvalidArgumentException $e) {
    // Corpo inválido: ignora, mas responde 200 para não gerar novas tentativas.
}

http_response_code(200);
