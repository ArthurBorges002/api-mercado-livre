<?php

/**
 * Configuração comum dos exemplos. As credenciais vêm de variáveis de ambiente
 * para nunca irem parar no Git:
 *
 *     ML_CLIENT_ID, ML_CLIENT_SECRET, ML_REDIRECT_URI
 *     ML_TOKEN_FILE  (opcional; padrão: pasta temporária do sistema)
 *     ML_CA_BUNDLE   (opcional; caminho de um cacert.pem, útil no Windows)
 */

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

use ApiMercadoLivre\Config;
use ApiMercadoLivre\Http\CurlHttpClient;
use ApiMercadoLivre\MercadoLivre;
use ApiMercadoLivre\Storage\FileTokenStorage;

$config = new Config(
    (string) getenv('ML_CLIENT_ID'),
    (string) getenv('ML_CLIENT_SECRET'),
    (string) getenv('ML_REDIRECT_URI')
);

$arquivoToken = getenv('ML_TOKEN_FILE') ?: sys_get_temp_dir() . '/ml_token.json';
$caBundle = getenv('ML_CA_BUNDLE') ?: null;

$ml = new MercadoLivre($config, new FileTokenStorage($arquivoToken), new CurlHttpClient(20, $caBundle));
