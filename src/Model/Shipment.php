<?php

declare(strict_types=1);

namespace ApiMercadoLivre\Model;

/**
 * Envio do Mercado Livre (GET /shipments/{id}), com o endereço de entrega já arrumado.
 *
 * A API devolve dois formatos. No novo (cabeçalho x-format-new, o que Shipments usa),
 * o endereço fica em destination.shipping_address, o destinatário em destination e a
 * modalidade em logistic.type. No antigo, tudo fica em receiver_address e logistic_type.
 * Esta classe lê os dois. O envio completo, como veio da API, fica em raw().
 */
final class Shipment
{
    /** Descrição de cada logistic.type, para observações e telas. */
    private const MODALIDADES = [
        'fulfillment' => 'Full',
        'cross_docking' => 'Mercado Envios (coleta)',
        'drop_off' => 'Mercado Envios (agência)',
        'xd_drop_off' => 'Mercado Envios (agência)',
        'self_service' => 'Mercado Envios Flex',
        'default' => 'Envio próprio',
    ];

    /** @var array<mixed> */
    private array $raw;

    /**
     * @param array<mixed> $raw
     */
    private function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    /**
     * @param array<mixed> $dados resposta de GET /shipments/{id}
     */
    public static function fromArray(array $dados): self
    {
        if (empty($dados['id'])) {
            throw new \InvalidArgumentException('Envio sem id.');
        }

        return new self($dados);
    }

    public function id(): int
    {
        return (int) $this->raw['id'];
    }

    /** pending, handling, ready_to_ship, shipped, delivered, cancelled... */
    public function status(): string
    {
        return (string) ($this->raw['status'] ?? '');
    }

    /** fulfillment, cross_docking, drop_off, xd_drop_off, self_service, default... (vazio se não vier) */
    public function logisticType(): string
    {
        return (string) ($this->raw['logistic']['type'] ?? $this->raw['logistic_type'] ?? '');
    }

    /** Ex.: "Mercado Envios (agência)". Tipo desconhecido volta como veio; sem tipo, vazio. */
    public function logisticDescription(): string
    {
        $tipo = $this->logisticType();

        return self::MODALIDADES[$tipo] ?? $tipo;
    }

    public function trackingNumber(): ?string
    {
        return empty($this->raw['tracking_number']) ? null : (string) $this->raw['tracking_number'];
    }

    public function receiverName(): string
    {
        return $this->texto($this->raw['destination']['receiver_name'] ?? $this->enderecoBruto()['receiver_name'] ?? null);
    }

    /** Telefone do destinatário. O ML costuma mascarar ("XXXXXXX"): nesse caso volta vazio. */
    public function receiverPhone(): string
    {
        $telefone = $this->texto($this->raw['destination']['receiver_phone'] ?? $this->enderecoBruto()['receiver_phone'] ?? null);

        return preg_match('/\d/', $telefone) ? $telefone : '';
    }

    /**
     * Endereço de entrega, sempre com as mesmas chaves (texto vazio quando não vier):
     * street_name, street_number, comment, neighborhood, city, state (sigla, ex.: MG),
     * state_name, zip_code (só números) e address_line.
     *
     * @return array<string, string>
     */
    public function address(): array
    {
        $endereco = $this->enderecoBruto();
        $estado = $endereco['state'] ?? null;
        $idEstado = $this->texto(is_array($estado) ? ($estado['id'] ?? null) : $estado);

        return [
            'street_name' => $this->texto($endereco['street_name'] ?? null),
            'street_number' => $this->texto($endereco['street_number'] ?? null),
            'comment' => $this->texto($endereco['comment'] ?? null),
            'neighborhood' => $this->nome($endereco['neighborhood'] ?? null),
            'city' => $this->nome($endereco['city'] ?? null),
            'state' => preg_match('/^(?:BR-)?([A-Z]{2})$/', $idEstado, $m) ? $m[1] : '',
            'state_name' => is_array($estado) ? $this->texto($estado['name'] ?? null) : '',
            'zip_code' => (string) preg_replace('/\D/', '', $this->texto($endereco['zip_code'] ?? null)),
            'address_line' => $this->texto($endereco['address_line'] ?? null),
        ];
    }

    /**
     * Envio completo como veio da API, para guardar ou consultar campos extras.
     *
     * @return array<mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    /** @return array<mixed> */
    private function enderecoBruto(): array
    {
        $endereco = $this->raw['destination']['shipping_address'] ?? $this->raw['receiver_address'] ?? [];

        return is_array($endereco) ? $endereco : [];
    }

    /** Campo que pode vir como texto ou como {"id":..., "name":...}. */
    private function nome($valor): string
    {
        return $this->texto(is_array($valor) ? ($valor['name'] ?? null) : $valor);
    }

    private function texto($valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }
}
