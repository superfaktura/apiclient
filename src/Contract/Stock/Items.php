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
}
