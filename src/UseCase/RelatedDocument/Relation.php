<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\RelatedDocument;

use SuperFaktura\ApiClient\Contract\RelatedDocument\DocumentType;

final class Relation
{
    public function __construct(
        public int $parent_id,
        public DocumentType $parent_type,
        public int $child_id,
        public DocumentType $child_type,
    ) {
    }
}
