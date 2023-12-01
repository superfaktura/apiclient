<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Utils;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

final readonly class AssertRequest
{
    /**
     * @param array<string, string> $expected_headers
     */
    public function __construct(
        private ?RequestInterface $request,
        private ?string $expected_request_method,
        private ?string $expected_uri,
        private ?string $expected_request_body,
        private array $expected_headers,
    ) {
    }

    public function assert(): void
    {
        Assert::assertNotNull($this->request);

        if ($this->expected_request_method !== null) {
            Assert::assertSame($this->expected_request_method, $this->request->getMethod());
        }

        if ($this->expected_uri !== null) {
            Assert::assertSame($this->expected_uri, $this->request->getUri()->getPath());
        }

        if ($this->expected_request_body !== null) {
            Assert::assertJsonStringEqualsJsonString($this->expected_request_body, (string) $this->request->getBody());
        }

        $this->assertHeaders();
    }

    private function assertHeaders(): void
    {
        foreach ($this->expected_headers as $key => $expected_value) {
            Assert::assertSame($expected_value, $this->request?->getHeaderLine($key));
        }
    }
}
