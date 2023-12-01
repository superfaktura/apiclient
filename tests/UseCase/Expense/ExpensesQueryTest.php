<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Expense;

use PHPUnit\Framework\TestCase;
use SuperFaktura\ApiClient\Filter\Sort;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\UseCase\Expense\ExpensesQuery;

#[CoversClass(ExpensesQuery::class)]
#[CoversClass(Sort::class)]
final class ExpensesQueryTest extends TestCase
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

        new ExpensesQuery(page: $page);
    }
}
