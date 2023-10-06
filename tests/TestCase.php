<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Exception\RequestException;
use Fig\Http\Message\RequestMethodInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\RateLimit;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const ERROR_COMMUNICATING_WITH_SERVER_MESSAGE = 'Error communicating with server';

    protected const JSON_ENCODE_FAILURE_MESSAGE = 'Inf and NaN cannot be JSON encoded';

    /**
     * @var array<array{request?: RequestInterface, response?: ResponseInterface}>
     */
    protected array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->history = [];
    }

    protected static function getApiResponse(
        int $status_code = StatusCodeInterface::STATUS_IM_A_TEAPOT,
        array $data = [],
        ?RateLimit $rate_limit_daily = null,
        ?RateLimit $rate_limit_monthly = null,
    ): Response {
        return new Response(
            status_code: $status_code,
            data: $data,
            rate_limit_daily: $rate_limit_daily ?? new RateLimit(
                limit: 1000,
                remaining: 999,
                resets_at: new \DateTimeImmutable('2023-01-01 23:01:02'),
            ),
            rate_limit_monthly: $rate_limit_monthly ?? new RateLimit(
                limit: 10000,
                remaining: 9999,
                resets_at: new \DateTimeImmutable('2023-01-31 23:00:00'),
            ),
        );
    }

    protected function getHttpClientWithMockResponse(MessageInterface ...$responses): Client
    {
        $modified_responses = [];

        foreach ($responses as $response) {
            if (!$response->hasHeader('X-RateLimit-DailyLimit')) {
                // Set default rate limit headers
                $response = $response
                    ->withHeader('X-RateLimit-DailyLimit', '1000')
                    ->withHeader('X-RateLimit-DailyRemaining', '999')
                    ->withHeader('X-RateLimit-DailyReset', '01.01.2099 00:00:00')
                    ->withHeader('X-RateLimit-MonthlyLimit', '1000')
                    ->withHeader('X-RateLimit-MonthlyRemaining', '999')
                    ->withHeader('X-RateLimit-MonthlyReset', '01.01.2099 00:00:00');
            }

            $modified_responses[] = $response;
        }

        $handlerStack = HandlerStack::create(
            new MockHandler($modified_responses),
        );
        $handlerStack->push(Middleware::history($this->history));

        return new Client([
            'handler' =>  $handlerStack,
        ]);
    }

    protected function getHttpClientWithMockRequestException(): Client
    {
        return new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new RequestException(self::ERROR_COMMUNICATING_WITH_SERVER_MESSAGE, new Request('GET', 'test')),
                ]),
            ),
        ]);
    }

    protected function getHttpOkResponse(): MessageInterface
    {
        return (new \GuzzleHttp\Psr7\Response(StatusCodeInterface::STATUS_OK, [], '[]'))
            ->withHeader('X-RateLimit-DailyLimit', '1000')
            ->withHeader('X-RateLimit-DailyRemaining', '999')
            ->withHeader('X-RateLimit-DailyReset', '01.01.2099 00:00:00')
            ->withHeader('X-RateLimit-MonthlyLimit', '1000')
            ->withHeader('X-RateLimit-MonthlyRemaining', '999')
            ->withHeader('X-RateLimit-MonthlyReset', '01.01.2099 00:00:00');
    }

    protected function getHttpOkResponseContainingInvalidJson(): MessageInterface
    {
        return (new \GuzzleHttp\Psr7\Response(StatusCodeInterface::STATUS_OK, [], '{'));
    }

    protected function getLastRequest(): ?RequestInterface
    {
        return $this->history[0]['request'] ?? null;
    }

    /**
     * @throws \RuntimeException Cannot read fixture
     */
    protected function jsonFromFixture(string $fixture_path): string
    {
        $content = file_get_contents($fixture_path);

        if ($content === false) {
            throw new \RuntimeException(sprintf('Cannot read fixture "%s"', $fixture_path));
        }

        return $content;
    }

    /**
     * @throws \RuntimeException Cannot read fixture
     * @throws \JsonException Cannot decode fixture
     *
     * @return array<string|int, mixed>
     */
    protected function arrayFromFixture(string $fixture_path): array
    {
        return (array) json_decode(
            $this->jsonFromFixture($fixture_path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    protected static function assertPostRequest(RequestInterface $request): void
    {
        self::assertSame(RequestMethodInterface::METHOD_POST, $request->getMethod());
    }

    protected static function assertDeleteRequest(RequestInterface $request): void
    {
        self::assertSame(RequestMethodInterface::METHOD_DELETE, $request->getMethod());
    }

    protected static function assertContentTypeJson(RequestInterface $request): void
    {
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }
}
