<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Handler\MockHandler;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Exception\RequestException;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\RateLimit;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
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

    protected function getHttpClientWithMockResponse(ResponseInterface ...$responses): Client
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

        return new Client([
            'handler' => HandlerStack::create(
                new MockHandler($modified_responses),
            ),
        ]);
    }

    protected function getHttpClientWithMockRequestException(): Client
    {
        return new Client([
            'handler' => HandlerStack::create(
                new MockHandler([
                    new RequestException('Error communicating with server', new Request('GET', 'test')),
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
}
