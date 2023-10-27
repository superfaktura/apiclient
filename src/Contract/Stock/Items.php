<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Stock;

use SuperFaktura\ApiClient\Response\Response;

interface Items
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws CannotCreateItemException
     */
    public function create(array $data): Response;

    /**
     * @throws ItemNotFoundException
     * @throws CannotGetItemByIdException
     */
    public function getById(int $id): Response;

    /**
     * @throws CannotGetAllItemsException
     */
    public function getAll(ItemsQuery $query = new ItemsQuery()): Response;

    /**
     * @param array<string, mixed> $data
     *
     * @throws ItemNotFoundException
     * @throws CannotUpdateItemException
     */
    public function update(int $id, array $data): Response;
}
