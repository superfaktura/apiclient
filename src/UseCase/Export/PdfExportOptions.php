<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Export;

use SuperFaktura\ApiClient\Contract\Language;
use SuperFaktura\ApiClient\Contract\Export\DocumentSort;

final class PdfExportOptions
{
    public function __construct(
        public ?Language $language = null,
        public bool $hide_payment_info = false,
        public bool $hide_signature = false,
        public ?DocumentSort $document_sort = null,
    ) {
    }
}
