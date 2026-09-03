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
        foreach ([1, '1', true, 2, 3, 4] as $value) {
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

    public static function messageDataProvider(): \Generator
    {
        yield 'string message is returned as is' => ['expected' => 'Tag bol uložený', 'value' => 'Tag bol uložený'];
        yield 'empty string stays empty' => ['expected' => '', 'value' => ''];
        yield 'null yields an empty string' => ['expected' => '', 'value' => null];

        // Non-string values are rendered rather than discarded: these accessors
        // feed exception messages, and an empty one tells the caller nothing.
        yield 'numeric message keeps its value' => ['expected' => '422', 'value' => 422];
        yield 'boolean message keeps its value' => ['expected' => '1', 'value' => true];
        yield 'listed message is unwrapped' => ['expected' => 'nested', 'value' => ['nested']];
        yield 'several listed messages are joined' => ['expected' => 'first, second', 'value' => ['first', 'second']];
        yield 'field map is joined' => [
            'expected' => 'Dokument musí obsahovať aspoň jednu položku',
            'value' => ['type' => ['Dokument musí obsahovať aspoň jednu položku']],
        ];
    }

    #[DataProvider('messageDataProvider')]
    public function testGetMessage(string $expected, mixed $value): void
    {
        self::assertSame($expected, self::getApiResponse(data: ['message' => $value])->getMessage());
    }

    public function testGetMessageWithoutMessageProperty(): void
    {
        self::assertSame('', self::getApiResponse(data: [])->getMessage());
    }

    #[DataProvider('messageDataProvider')]
    public function testGetErrorMessage(string $expected, mixed $value): void
    {
        self::assertSame($expected, self::getApiResponse(data: ['error_message' => $value])->getErrorMessage());
    }

    public function testGetErrorMessageWithoutErrorMessageProperty(): void
    {
        self::assertSame('', self::getApiResponse(data: [])->getErrorMessage());
    }

    public static function normalizedErrorMessagesDataProvider(): \Generator
    {
        // Shapes the API is known to send.
        yield 'scalar error message becomes a single element list' => [
            'expected' => ['You can\'t create invoice'],
            'error_message' => 'You can\'t create invoice',
        ];

        yield 'field map keeps its keys and lists' => [
            'expected' => ['type' => ['Dokument musí obsahovať aspoň jednu položku']],
            'error_message' => ['type' => ['Dokument musí obsahovať aspoň jednu položku']],
        ];

        yield 'several messages of one field are preserved in order' => [
            'expected' => ['type' => ['Prvá chyba', 'Druhá chyba']],
            'error_message' => ['type' => ['Prvá chyba', 'Druhá chyba']],
        ];

        yield 'several fields are preserved' => [
            'expected' => ['due' => ['Neplatný dátum'], 'type' => ['Chýba položka']],
            'error_message' => ['due' => ['Neplatný dátum'], 'type' => ['Chýba položka']],
        ];

        yield 'plain list is returned as is' => [
            'expected' => ['Incorrect currency'],
            'error_message' => ['Incorrect currency'],
        ];

        // Values are cast to string, but never discarded.
        yield 'numeric error message keeps its value' => ['expected' => ['422'], 'error_message' => 422];
        yield 'float error message keeps its value' => ['expected' => ['1.5'], 'error_message' => 1.5];
        yield 'true error message keeps its value' => ['expected' => ['1'], 'error_message' => true];

        yield 'numeric message of a field keeps its value' => [
            'expected' => ['type' => ['123']],
            'error_message' => ['type' => [123]],
        ];

        yield 'scalar value of a field keeps its value' => [
            'expected' => ['type' => '456'],
            'error_message' => ['type' => 456],
        ];

        // A payload nested deeper than the return type can express is joined,
        // not dropped.
        yield 'deeply nested message is joined instead of discarded' => [
            'expected' => ['type' => ['inner' => 'too deep']],
            'error_message' => ['type' => ['inner' => ['too deep']]],
        ];

        yield 'deeply nested messages are joined in order' => [
            'expected' => ['type' => ['inner' => 'first, second']],
            'error_message' => ['type' => ['inner' => ['first', 'second']]],
        ];

        // Absent and empty values normalise to an empty message.
        yield 'null error message yields an empty message' => ['expected' => [''], 'error_message' => null];
        yield 'empty string error message yields an empty message' => ['expected' => [''], 'error_message' => ''];
        yield 'empty array yields an empty result' => ['expected' => [], 'error_message' => []];

        yield 'null message of a field yields an empty message' => [
            'expected' => ['type' => ['']],
            'error_message' => ['type' => [null]],
        ];
    }

    /**
     * @param array<string|int, string|string[]> $expected
     */
    #[DataProvider('normalizedErrorMessagesDataProvider')]
    public function testGetNormalizedErrorMessages(array $expected, mixed $error_message): void
    {
        self::assertSame(
            $expected,
            self::getApiResponse(data: ['error' => 1, 'error_message' => $error_message])
                ->getNormalizedErrorMessages(),
        );
    }

    public function testGetNormalizedErrorMessagesWithoutErrorMessageProperty(): void
    {
        self::assertSame([''], self::getApiResponse(data: ['error' => 1])->getNormalizedErrorMessages());
    }
}
