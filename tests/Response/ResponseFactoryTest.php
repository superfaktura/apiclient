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
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Response\CannotCreateResponseException;

#[CoversClass(ResponseFactory::class)]
#[CoversClass(Response::class)]
#[CoversClass(BinaryResponse::class)]
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
            actual: $this->factory->createFromJsonResponse($response),
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

        $this->factory->createFromJsonResponse($response);
    }

    public function testCreateFromHttpResponseWithoutRateLimitHeaders(): void
    {
        self::assertEquals(
            expected: new Response(
                status_code: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
                data: [
                    'error' => 1,
                    'message' => 'Error: 500',
                    'error_message' => 'Error: 500',
                ],
            ),
            actual: $this->factory->createFromJsonResponse(
                new Psr7\Response(
                    status: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
                    headers: [],
                    body: '{"error":1,"message":"Error: 500","error_message":"Error: 500"}',
                ),
            ),
        );
    }

    public static function createBinaryResponseProvider(): \Generator
    {
        yield 'file is returned as binary response' => [
            'fixture' => __DIR__ . '/fixtures/foo.pdf',
            'response' => self::getPsrBinaryResponse(
                filename: __DIR__ . '/fixtures/foo.pdf',
                status_code: StatusCodeInterface::STATUS_OK,
                headers: ['Content-Type' => 'application/pdf'],
            ),
            'content_type' => 'application/pdf',
        ];

        yield 'rate limit values are extracted from response headers and converted to UTC' => [
            'fixture' => __DIR__ . '/fixtures/export.zip',
            'response' => self::getPsrBinaryResponse(
                filename: __DIR__ . '/fixtures/export.zip',
                status_code: StatusCodeInterface::STATUS_OK,
                headers: [
                    'Content-Type' => 'application/zip',
                    'X-RateLimit-DailyLimit' => '500',
                    'X-RateLimit-DailyRemaining' => '499',
                    'X-RateLimit-DailyReset' => '02.01.2024 00:00:00',
                    'X-RateLimit-MonthlyLimit' => '5000',
                    'X-RateLimit-MonthlyRemaining' => '4999',
                    'X-RateLimit-MonthlyReset' => '01.02.2024 00:00:00',
                ],
            ),
            'content_type' => 'application/zip',
            'rate_limit_daily' => new RateLimit(
                limit: 500,
                remaining: 499,
                resets_at: new \DateTimeImmutable('2024-01-01 23:00:00'),
            ),
            'rate_limit_monthly' => new RateLimit(
                limit: 5000,
                remaining: 4999,
                resets_at: new \DateTimeImmutable('2024-01-31 23:00:00'),
            ),
        ];
    }

    #[DataProvider('createBinaryResponseProvider')]
    public function testCreateBinaryResponseFromHttpResponse(
        string $fixture,
        ResponseInterface $http_response,
        string $content_type,
        ?RateLimit $rate_limit_daily = null,
        ?RateLimit $rate_limit_monthly = null,
    ): void {
        $response = $this->factory->createFromBinaryResponse($http_response);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->status_code);
        self::assertStringEqualsFile($fixture, (string) stream_get_contents($response->data));
        self::assertEquals($content_type, $response->content_type);
        self::assertEquals($rate_limit_daily, $response->rate_limit_daily);
        self::assertEquals($rate_limit_monthly, $response->rate_limit_monthly);
    }

    public function testCreateBinaryResponseFromUnavailableResource(): void
    {
        $this->expectException(CannotCreateResponseException::class);

        $http_response = new Psr7\Response();
        $http_response->getBody()->detach();

        $this->factory->createFromBinaryResponse($http_response);
    }

    public function testCreateBinaryResponseFromEmptyBody(): void
    {
        $this->expectException(CannotCreateResponseException::class);

        $http_response = new Psr7\Response(
            status: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
        );

        $this->factory->createFromBinaryResponse($http_response);
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
