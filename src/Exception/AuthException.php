<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Exception;

/**
 * Falha de autorização: conta não conectada, token revogado ou refresh token inválido.
 *
 * Quando acontece, a conta do Mercado Livre precisa ser autorizada de novo
 * (botão "Conectar ao Mercado Livre" no sistema).
 */
class AuthException extends ApiException
{
}
