<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Response;

use GuzzleHttp\Psr7;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Response\ResponseFactory;

#[CoversClass(ResponseFactory::class)]
#[CoversClass(Response::class)]
#[CoversClass(RateLimit::class)]
final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ResponseFactory();
    }

    /**
     * @return \Generator<array{expected: Response, response: ResponseInterface}>
     */
    public static function createFromHttpResponseProvider(): \Generator
    {
        yield 'json response is converted to array' => [
            'expected' => self::getApiResponse(
                status_code: StatusCodeInterface::STATUS_OK,
                data: ['Invoice' => ['id' => 1]],
            ),
            'response' => self::getPsrResponse(
                status_code: StatusCodeInterface::STATUS_OK,
                body: '{"Invoice":{"id":1}}',
            ),
        ];

        yield 'rate limit values are extracted from response headers and converted to UTC' => [
            'expected' => self::getApiResponse(
                rate_limit_daily: new RateLimit(
                    limit: 500,
                    remaining: 499,
                    resets_at: new \DateTimeImmutable('2024-01-01 23:00:00'),
                ),
                rate_limit_monthly: new RateLimit(
                    limit: 5000,
                    remaining: 4999,
                    resets_at: new \DateTimeImmutable('2024-01-31 23:00:00'),
                ),
            ),
            'response' => self::getPsrResponse(
                headers: [
                    'X-RateLimit-DailyLimit' => '500',
                    'X-RateLimit-DailyRemaining' => '499',
                    'X-RateLimit-DailyReset' => '02.01.2024 00:00:00',
                    'X-RateLimit-MonthlyLimit' => '5000',
                    'X-RateLimit-MonthlyRemaining' => '4999',
                    'X-RateLimit-MonthlyReset' => '01.02.2024 00:00:00',
                ],
            ),
        ];
    }

    #[DataProvider('createFromHttpResponseProvider')]
    public function testCreateFromHttpResponse(Response $expected, ResponseInterface $response): void
    {
        self::assertEquals(
            expected: $expected,
            actual: $this->factory->createFromHttpResponse($response),
        );
    }

    /**
     * @return \Generator<array{response: ResponseInterface}>
     */
    public static function createFromHttpResponseInvalidRateLimitResetProvider(): \Generator
    {
        yield 'invalid daily reset datetime' => [
            'response' => self::getPsrResponse(
                headers: [
                    'X-RateLimit-DailyReset' => 'foo',
                ],
            ),
        ];

        yield 'invalid monthly reset datetime' => [
            'response' => self::getPsrResponse(
                headers: [
                    'X-RateLimit-MonthlyReset' => 'bar',
                ],
            ),
        ];
    }

    #[DataProvider('createFromHttpResponseInvalidRateLimitResetProvider')]
    public function testCreateFromHttpResponseInvalidRateLimitReset(ResponseInterface $response): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $this->factory->createFromHttpResponse($response);
    }

    /**
     * @param array<string, string> $headers
     */
    private static function getPsrResponse(
        int $status_code = StatusCodeInterface::STATUS_IM_A_TEAPOT,
        string $body = '{}',
        array $headers = [],
    ): Psr7\Response {
        return new Psr7\Response(
            status: $status_code,
            headers: array_merge(
                [
                    'X-RateLimit-DailyLimit' => '1000',
                    'X-RateLimit-DailyRemaining' => '999',
                    'X-RateLimit-DailyReset' => '02.01.2023 00:01:02',
                    'X-RateLimit-MonthlyLimit' => '10000',
                    'X-RateLimit-MonthlyRemaining' => '9999',
                    'X-RateLimit-MonthlyReset' => '01.02.2023 00:00:00',
                ],
                $headers,
            ),
            body: $body,
        );
    }
}
