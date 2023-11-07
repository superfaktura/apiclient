<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Stock;

use SuperFaktura\ApiClient\Response\Response;

interface Movements
{
    /**
     * @param array{StockLog: array<string, mixed>[]} $data
     *
     * @throws CannotCreateMovementException
     * @throws ItemNotFoundException
     */
    public function create(int $item_id, array $data): Response;

    /**
     * @param array{StockLog: array<string, mixed>[]} $data
     *
     * @throws CannotCreateMovementException
     * @throws ItemNotFoundException
     */
    public function createWithSku(string $sku, array $data): Response;

    /**
     * @throws CannotGetAllMovementsException
     */
    public function getAll(int $id, MovementsQuery $query = new MovementsQuery()): Response;
}
