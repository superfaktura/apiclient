<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

final readonly class Response
{
    public function __construct(
        public int $status_code,
        public array $data,
        public ?RateLimit $rate_limit_daily = null,
        public ?RateLimit $rate_limit_monthly = null,
    ) {
    }

    public function isError(): bool
    {
        return (int) ($this->data['error'] ?? 0) > 0;
    }
}
