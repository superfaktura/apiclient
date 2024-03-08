<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

use Psr\Http\Message\ResponseInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    private const RATE_LIMIT_RESET_DATETIME_FORMAT = 'd.m.Y H:i:s';

    private const SF_API_TIMEZONE = 'Europe/Bratislava';

    private const RESPONSE_TIMEZONE = 'UTC';

    public function createFromJsonResponse(ResponseInterface $response): Response
    {
        return new Response(
            status_code: $response->getStatusCode(),
            data: (array) json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
            rate_limit_daily: $this->getDailyRateLimit($response),
            rate_limit_monthly: $this->getMonthlyRateLimit($response),
        );
    }

    public function createFromBinaryResponse(ResponseInterface $response): BinaryResponse
    {
        $resource = $response->getBody()->detach();

        if (!is_resource($resource)) {
            throw new CannotCreateResponseException('Stream resource is not available');
        }

        if (!$response->hasHeader('Content-Type')) {
            throw new CannotCreateResponseException('Missing content type header');
        }

        return new BinaryResponse(
            status_code: $response->getStatusCode(),
            content_type: $response->getHeaderLine('Content-Type'),
            data: $resource,
            rate_limit_daily: $this->getDailyRateLimit($response),
            rate_limit_monthly: $this->getMonthlyRateLimit($response),
        );
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function getDatetimeImmutable(string $input): \DateTimeImmutable
    {
        $datetime = \DateTimeImmutable::createFromFormat(
            self::RATE_LIMIT_RESET_DATETIME_FORMAT,
            $input,
            new \DateTimeZone(self::SF_API_TIMEZONE),
        );

        if ($datetime === false) {
            throw new \UnexpectedValueException(sprintf('Invalid datetime "%s"', $input));
        }

        return $datetime->setTimezone(new \DateTimeZone(self::RESPONSE_TIMEZONE));
    }

    private function getDailyRateLimit(ResponseInterface $response): ?RateLimit
    {
        if (!$response->hasHeader('X-RateLimit-DailyLimit')) {
            return null;
        }

        return new RateLimit(
            limit: (int) $response->getHeaderLine('X-RateLimit-DailyLimit'),
            remaining: (int) $response->getHeaderLine('X-RateLimit-DailyRemaining'),
            resets_at: $this->getDatetimeImmutable(
                $response->getHeaderLine('X-RateLimit-DailyReset'),
            ),
        );
    }

    private function getMonthlyRateLimit(ResponseInterface $response): ?RateLimit
    {
        if (!$response->hasHeader('X-RateLimit-MonthlyLimit')) {
            return null;
        }

        return new RateLimit(
            limit: (int) $response->getHeaderLine('X-RateLimit-MonthlyLimit'),
            remaining: (int) $response->getHeaderLine('X-RateLimit-MonthlyRemaining'),
            resets_at: $this->getDatetimeImmutable(
                $response->getHeaderLine('X-RateLimit-MonthlyReset'),
            ),
        );
    }
}
