<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Tag;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

interface Tags
{
    /**
     * @throws CannotGetAllTagsException
     */
    public function getAll(): Response;

    /**
     * @throws TagAlreadyExistsException
     * @throws CannotCreateTagException
     * @throws CannotCreateRequestException
     */
    public function create(string $tag): Response;

    /**
     * @throws CannotUpdateTagException
     * @throws CannotCreateRequestException
     * @throws TagNotFoundException
     */
    public function update(int $id, string $tag): Response;

    /**
     * @throws CannotDeleteTagException
     * @throws TagNotFoundException
     */
    public function delete(int $id): void;
}
