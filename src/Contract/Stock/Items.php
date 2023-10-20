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
}
