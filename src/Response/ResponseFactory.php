<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

use Psr\Http\Message\ResponseInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    private const RATE_LIMIT_RESET_DATETIME_FORMAT = 'd.m.Y H:i:s';

    private const SF_API_TIMEZONE = 'Europe/Bratislava';

    private const RESPONSE_TIMEZONE = 'UTC';

    public function createFromHttpResponse(ResponseInterface $response): Response
    {
        return new Response(
            status_code: $response->getStatusCode(),
            data: (array) json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR),
            rate_limit_daily: new RateLimit(
                limit: (int) $response->getHeaderLine('X-RateLimit-DailyLimit'),
                remaining: (int) $response->getHeaderLine('X-RateLimit-DailyRemaining'),
                resets_at: $this->createDatetimeImmutable(
                    $response->getHeaderLine('X-RateLimit-DailyReset'),
                ),
            ),
            rate_limit_monthly: new RateLimit(
                limit: (int) $response->getHeaderLine('X-RateLimit-MonthlyLimit'),
                remaining: (int) $response->getHeaderLine('X-RateLimit-MonthlyRemaining'),
                resets_at: $this->createDatetimeImmutable(
                    $response->getHeaderLine('X-RateLimit-MonthlyReset'),
                ),
            ),
        );
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function createDatetimeImmutable(string $input): \DateTimeImmutable
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
}
