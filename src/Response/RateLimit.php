<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

final readonly class RateLimit
{
    public function __construct(
        public int $limit,
        public int $remaining,
        public \DateTimeImmutable $resets_at,
    ) {
    }
}
