<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Webhook;

/**
 * Aviso enviado pelo Mercado Livre para a URL de notificações do aplicativo.
 *
 *     $aviso = Notification::fromJson(file_get_contents('php://input'));
 *     if ($aviso->isOrder() && $aviso->belongsTo($sellerId)) {
 *         // guardar $aviso->resourceId() numa fila e responder 200 na hora
 *     }
 *
 * Qualquer um pode chamar a URL do webhook: nunca confie nos dados do aviso.
 * Use só o id para buscar o recurso verdadeiro na API.
 */
final class Notification
{
    public const TOPIC_PEDIDOS = 'orders_v2';

    private string $topic;
    private string $resource;
    private int $userId;
    private string $applicationId;
    private int $attempts;

    public function __construct(string $topic, string $resource, int $userId, string $applicationId, int $attempts)
    {
        $this->topic = $topic;
        $this->resource = $resource;
        $this->userId = $userId;
        $this->applicationId = $applicationId;
        $this->attempts = $attempts;
    }

    /**
     * @throws \InvalidArgumentException quando o corpo não é um aviso válido
     */
    public static function fromJson(string $corpo): self
    {
        $dados = json_decode($corpo, true);
        if (!is_array($dados) || empty($dados['topic']) || empty($dados['resource'])) {
            throw new \InvalidArgumentException('Corpo da notificação inválido.');
        }

        return new self(
            (string) $dados['topic'],
            (string) $dados['resource'],
            (int) ($dados['user_id'] ?? 0),
            (string) ($dados['application_id'] ?? ''),
            (int) ($dados['attempts'] ?? 0)
        );
    }

    public function topic(): string
    {
        return $this->topic;
    }

    /** Caminho do recurso, ex.: /orders/2000001234 */
    public function resource(): string
    {
        return $this->resource;
    }

    /** Conta (vendedor) a que o aviso se refere. */
    public function userId(): int
    {
        return $this->userId;
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function isOrder(): bool
    {
        return $this->topic === self::TOPIC_PEDIDOS;
    }

    /**
     * Id numérico no fim do resource (ex.: 2000001234), ou null se o formato for inesperado.
     */
    public function resourceId(): ?string
    {
        return preg_match('#^/[a-z_]+/(\d+)$#', $this->resource, $m) ? $m[1] : null;
    }

    /**
     * Confere se o aviso é da conta e do aplicativo esperados.
     */
    public function belongsTo(int $sellerId, ?string $applicationId = null): bool
    {
        if ($this->userId !== $sellerId) {
            return false;
        }

        return $applicationId === null || $this->applicationId === $applicationId;
    }
}
