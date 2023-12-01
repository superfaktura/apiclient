<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Filter;

interface QueryParamsConvertor
{
    /**
     * @param array<string, string|int|bool|float|null> $params
     */
    public function convert(array $params): string;
}
