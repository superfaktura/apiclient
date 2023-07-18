<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Authorization;

final readonly class Authorization
{
    public function __construct(
        public string $email,
        public string $key,
        public string $module,
        public string $app_title,
        public int $company_id,
    ) {
    }
}
