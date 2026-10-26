<?php

/**
 * Configuração comum dos exemplos. As credenciais vêm de variáveis de ambiente
 * para nunca irem parar no Git:
 *
 *     ML_CLIENT_ID, ML_CLIENT_SECRET, ML_REDIRECT_URI
 *     ML_TOKEN_FILE  (opcional; padrão: pasta temporária do sistema)
 *     ML_CA_BUNDLE   (opcional; caminho de um cacert.pem, útil no Windows)
 *
 * Se o servidor não repassa variáveis de ambiente (Apache com AllowOverride None),
 * crie examples/_env.php com chamadas putenv('NOME=valor'). Ele fica fora do Git.
 */

declare(strict_types=1);

if (is_file(__DIR__ . '/_env.php')) {
    require __DIR__ . '/_env.php';
}

require __DIR__ . '/../autoload.php';

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
}

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
