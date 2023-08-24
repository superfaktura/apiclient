<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

final readonly class Response
{
    public function __construct(
        public int $status_code,
        public array $data,
        public RateLimit $rate_limit_daily,
        public RateLimit $rate_limit_monthly,
    ) {
    }

    public function isError(): bool
    {
        return ((bool) filter_var(
            $this->data['error'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        )) === true;
    }
}
