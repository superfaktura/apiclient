<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Webhook;

final readonly class Webhook
{
    public function __construct(
        public string $url,
        #[\SensitiveParameter]
        public ?string $secret = null,
    ) {
        $parts = parse_url($url);

        if (
            filter_var($url, FILTER_VALIDATE_URL) === false
            || $parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['fragment'])
        ) {
            throw new \InvalidArgumentException('Webhook URL must be a public HTTPS URL without credentials or fragments.');
        }
    }
}
