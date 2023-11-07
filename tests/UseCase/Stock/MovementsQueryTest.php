<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Stock;

use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Contract\Stock\MovementsQuery;

#[CoversClass(MovementsQuery::class)]
final class MovementsQueryTest extends TestCase
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
        new MovementsQuery(page: $page);
    }

    /**
     * @return \Generator<int[]>
     */
    public static function invalidPerPageArgumentProvider(): \Generator
    {
        yield 'negative' => [-1];
        yield 'zero' => [0];
        yield 'more than max' => [201];
    }

    #[DataProvider('invalidPerPageArgumentProvider')]
    public function testInvalidPerPageArgument(int $per_page): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new MovementsQuery(per_page: $per_page);
    }
}
