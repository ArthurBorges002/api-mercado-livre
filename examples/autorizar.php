<?php

/**
 * Conectar uma conta do Mercado Livre.
 *
 * Abra esta página no navegador. Cadastre a URL dela como Redirect URI no
 * aplicativo do Mercado Livre: a mesma página inicia e conclui a autorização.
 */

declare(strict_types=1);

require __DIR__ . '/_config.php';

use MercadoLivreEcommerce\Exception\MercadoLivreException;

session_start();

if (!isset($_GET['code'])) {
    $_SESSION['ml_state'] = bin2hex(random_bytes(16));
    header('Location: ' . $ml->auth()->authorizationUrl($_SESSION['ml_state']));
    exit;
}

if (!isset($_GET['state'], $_SESSION['ml_state']) || !hash_equals($_SESSION['ml_state'], (string) $_GET['state'])) {
    http_response_code(400);
    exit('Retorno de autorização inválido. Tente conectar de novo.');
}
unset($_SESSION['ml_state']);

try {
    $token = $ml->connect((string) $_GET['code']);
    $conta = $ml->users()->me();
    echo 'Conectado como ' . htmlspecialchars((string) $conta['nickname']) . ' (id ' . $token->userId() . ').';
} catch (MercadoLivreException $e) {
    http_response_code(502);
    echo 'Não foi possível conectar: ' . htmlspecialchars($e->getMessage());
}
