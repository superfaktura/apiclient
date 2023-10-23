<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice;

use SuperFaktura\ApiClient\Contract\Invoice\Language;

final class Email
{
    /**
     * @param string[] $bcc
     * @param string[] $cc
     * @param string|null $subject default value as defined in templates
     * @param string|null $message default value as defined in templates
     */
    public function __construct(
        public string $email,
        public Language $pdf_language,
        public array $bcc = [],
        public array $cc = [],
        public ?string $subject = null,
        public ?string $message = null,
    ) {
    }
}
