<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

final class BinaryResponse
{
    /**
     * @param resource $data
     */
    public function __construct(
        public readonly int $status_code,
        public $data,
        public readonly ?RateLimit $rate_limit_daily = null,
        public readonly ?RateLimit $rate_limit_monthly = null,
    ) {
    }
}
