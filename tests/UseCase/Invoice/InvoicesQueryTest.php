<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use PHPUnit\Framework\TestCase;
use SuperFaktura\ApiClient\Filter\Sort;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\UseCase\Invoice\InvoicesQuery;

#[CoversClass(InvoicesQuery::class)]
#[CoversClass(Sort::class)]
final class InvoicesQueryTest extends TestCase
{
    /**
     * @return \Generator<int[]>
     */
    public static function invalidPageArgumentProvider(): \Generator
    {
        yield 'negative' => [-1];
        yield 'zero' => [0];
    }

    #[DataProvider('invalidPageArgumentProvider')]
    public function testInvalidPageArgument(int $page): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new InvoicesQuery(page: $page);
    }

    /**
     * @return \Generator<int[]>
     */
    public static function invalidItemsPerPageArgumentProvider(): \Generator
    {
        yield 'negative' => [-1];
        yield 'zero' => [0];
        yield 'more than max' => [201];
    }

    #[DataProvider('invalidItemsPerPageArgumentProvider')]
    public function testInvalidItemsPerPageArgument(int $items_per_page): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new InvoicesQuery(items_per_page: $items_per_page);
    }
}
