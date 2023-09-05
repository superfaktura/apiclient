<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Filter;

final class NamedParamsConvertor implements QueryParamsConvertor
{
    private const URLENCODED_COLON = '%3A';

    public function convert(array $params): string
    {
        return (string) mb_ereg_replace(
            pattern: '[=]',
            replacement: self::URLENCODED_COLON,
            string: http_build_query(
                data: $params,
                arg_separator: '/',
            ),
        );
    }
}
