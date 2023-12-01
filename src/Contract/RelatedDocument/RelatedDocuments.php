<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\RelatedDocument;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\RelatedDocument\Relation;

interface RelatedDocuments
{
    /**
     * @throws CannotLinkDocumentsException
     */
    public function link(Relation $relation): Response;

    /**
     * @throws CannotUnlinkDocumentsException
     */
    public function unlink(int $relation_id): void;
}
