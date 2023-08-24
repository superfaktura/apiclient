<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Response;

use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public static function isErrorDataProvider(): \Generator
    {
        foreach ([1, '1', true] as $value) {
            yield sprintf('response is error if error property is %s (%s)', $value, gettype($value)) => [
                'expected' => true,
                'response' => self::getApiResponse(
                    data: ['error' => $value],
                ),
            ];
        }

        foreach ([0, '0', false] as $value) {
            yield sprintf('response is not error if error property is %s (%s)', $value, gettype($value)) => [
                'expected' => false,
                'response' => self::getApiResponse(
                    data: ['error' => $value],
                ),
            ];
        }

        yield 'response is not error if does not contain error property' => [
            'expected' => false,
            'response' => self::getApiResponse(
                status_code: StatusCodeInterface::STATUS_OK,
                data: ['foo' => 'bar'],
            ),
        ];
    }

    #[DataProvider('isErrorDataProvider')]
    public function testIsError(bool $expected, Response $response): void
    {
        self::assertSame($expected, $response->isError());
    }
}
