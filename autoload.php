<?php

/**
 * Autoload para usar a biblioteca sem Composer:
 *
 *     require __DIR__ . '/api-mercado-livre/autoload.php';
 *
 * Com Composer, use o vendor/autoload.php normalmente.
 */
spl_autoload_register(function ($classe) {
    $prefixo = 'MercadoLivreEcommerce\\';
    if (strpos($classe, $prefixo) !== 0) {
        return;
    }

    $arquivo = __DIR__ . '/src/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});
