<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

/**
 * @see \SuperFaktura\ApiClient\Test\Response\ResponseTest
 */
final readonly class Response
{
    /**
     * @param array<string|int, mixed> $data
     */
    public function __construct(
        public int $status_code,
        public array $data,
        public ?RateLimit $rate_limit_daily = null,
        public ?RateLimit $rate_limit_monthly = null,
    ) {
    }

    public function isError(): bool
    {
        $error = $this->data['error'] ?? 0;

        return (is_bool($error) || is_numeric($error)) && (int) $error > 0;
    }

    public function getMessage(): string
    {
        return $this->getStringValue('message');
    }

    public function getErrorMessage(): string
    {
        return $this->getStringValue('error_message');
    }

    /**
     * Flattens the API's error_message into a predictable shape: either a list of
     * messages, or a field => message(s) map.
     *
     * Every message is returned as a string, but no message is ever discarded -
     * a numeric or boolean error_message keeps its value instead of collapsing to
     * an empty string, and a payload nested deeper than the return type can
     * express is joined rather than dropped.
     *
     * @return array<string|int, string|string[]>
     */
    public function getNormalizedErrorMessages(): array
    {
        $error_message = $this->data['error_message'] ?? null;

        if (!is_array($error_message)) {
            return [$this->toMessage($error_message)];
        }

        $normalized = [];

        foreach ($error_message as $field => $messages) {
            if (!is_array($messages)) {
                $normalized[$field] = $this->toMessage($messages);

                continue;
            }

            $field_messages = [];

            foreach ($messages as $key => $message) {
                $field_messages[$key] = $this->toMessage($message);
            }

            $normalized[$field] = $field_messages;
        }

        return $normalized;
    }

    private function getStringValue(string $key): string
    {
        $value = $this->data[$key] ?? '';

        return $this->toMessage($value);
    }

    /**
     * Renders one value as a message string.
     *
     * Scalars are cast, null becomes an empty string, and an array - which the
     * return type of getNormalizedErrorMessages() cannot represent at that depth -
     * is joined instead of being replaced by an empty string, so the text the API
     * sent still reaches the caller.
     */
    private function toMessage(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode(', ', array_filter(
                array_map($this->toMessage(...), $value),
                static fn (string $message): bool => $message !== '',
            ));
        }

        return '';
    }
}
