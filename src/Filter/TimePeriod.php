<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Filter;

final class TimePeriod
{
    public function __construct(
        public TimePeriodEnum $period,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
    ) {
    }
}
