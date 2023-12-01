<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Filter;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;

#[CoversClass(NamedParamsConvertor::class)]
final class NamedParamsConvertorTest extends TestCase
{
    private NamedParamsConvertor $convertor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->convertor = new NamedParamsConvertor();
    }

    /**
     * @return \Generator<array{expected: string, params: array<string, string|int|bool|float|null>}>
     */
    public static function convertProvider(): \Generator
    {
        yield 'no parameters no problem' => [
            'expected' => '',
            'params' => [],
        ];

        yield 'parameter name and value is separated by urlencoded ":"' => [
            'expected' => 'foo%3Abar',
            'params' => ['foo' => 'bar'],
        ];

        yield 'parameters are separated by "/"' => [
            'expected' => 'foo%3Abar/baz%3Aqux',
            'params' => ['foo' => 'bar', 'baz' => 'qux'],
        ];

        yield 'parameter values are urlencoded' => [
            'expected' => 'foo%3ASuperFakt%C3%BAra/bar%3A%3A%40%2F',
            'params' => ['foo' => 'SuperFaktúra', 'bar' => ':@/'],
        ];

        yield 'null parameter value is not included into query string' => [
            'expected' => '',
            'params' => ['foo' => null],
        ];
    }

    /**
     * @param array<string, string|int|bool|float|null> $params
     */
    #[DataProvider('convertProvider')]
    public function testConvert(string $expected, array $params): void
    {
        self::assertSame(
            expected: $expected,
            actual: $this->convertor->convert($params),
        );
    }
}
