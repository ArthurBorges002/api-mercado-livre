<?php

declare(strict_types=1);

namespace MercadoLivreEcommerce\Resource;

use MercadoLivreEcommerce\MercadoLivre;

final class Items
{
    /** Máximo de anúncios por chamada em /items?ids= */
    private const LOTE_MULTIGET = 20;

    /** Máximo por página em /users/{id}/items/search */
    private const POR_PAGINA = 50;

    private MercadoLivre $ml;

    public function __construct(MercadoLivre $ml)
    {
        $this->ml = $ml;
    }

    /**
     * Ids dos anúncios do vendedor (ex.: MLB1234567890).
     *
     * Para contas com mais de 1000 anúncios o Mercado Livre exige a busca do tipo
     * "scan"; ainda não implementado aqui.
     *
     * @return string[]
     */
    public function idsBySeller(int $sellerId, string $status = 'active'): array
    {
        $ids = [];
        $offset = 0;

        do {
            $pagina = $this->ml->get('/users/' . $sellerId . '/items/search', [
                'status' => $status,
                'offset' => $offset,
                'limit' => self::POR_PAGINA,
            ]);
            $resultados = $pagina['results'] ?? [];
            $ids = array_merge($ids, $resultados);
            $offset += self::POR_PAGINA;
            $total = (int) ($pagina['paging']['total'] ?? 0);
        } while ($resultados !== [] && $offset < $total);

        return $ids;
    }

    /**
     * Detalhes dos anúncios (título, seller_sku, variations...), em lotes de 20.
     *
     * @param string[] $itemIds
     * @return array<int, array<mixed>> um array por anúncio encontrado
     */
    public function getMany(array $itemIds): array
    {
        $anuncios = [];

        foreach (array_chunk($itemIds, self::LOTE_MULTIGET) as $lote) {
            $respostas = $this->ml->get('/items', ['ids' => implode(',', $lote)]);
            foreach ($respostas as $resposta) {
                if ((int) ($resposta['code'] ?? 0) === 200 && isset($resposta['body'])) {
                    $anuncios[] = $resposta['body'];
                }
            }
        }

        return $anuncios;
    }
}
