<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Utils;

use Psr\Http\Message\RequestInterface;
use Fig\Http\Message\RequestMethodInterface;

final class AssertRequestBuilder
{
    private ?RequestInterface $request;

    private ?string $expected_request_method = null;

    private ?string $expected_uri = null;

    private ?string $expected_request_body = null;

    /**
     * @var array<string, string>
     */
    private array $expected_headers = [];

    public function __construct(?RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @param \Fig\Http\Message\RequestMethodInterface::*|null $expected_request_method
     */
    public function withMethod(?string $expected_request_method): AssertRequestBuilder
    {
        $this->expected_request_method = $expected_request_method;

        return $this;
    }

    public function get(string $expected_uri): AssertRequestBuilder
    {
        $this->withUri($expected_uri);

        return $this->withMethod(RequestMethodInterface::METHOD_GET);
    }

    public function post(string $expected_uri): AssertRequestBuilder
    {
        $this->withUri($expected_uri);

        return $this->withMethod(RequestMethodInterface::METHOD_POST);
    }

    public function patch(string $expected_uri): AssertRequestBuilder
    {
        $this->withUri($expected_uri);

        return $this->withMethod(RequestMethodInterface::METHOD_PATCH);
    }

    public function delete(string $expected_uri): AssertRequestBuilder
    {
        $this->withUri($expected_uri);

        return $this->withMethod(RequestMethodInterface::METHOD_DELETE);
    }

    public function withUri(string $expected_uri): AssertRequestBuilder
    {
        $this->expected_uri = $expected_uri;

        return $this;
    }

    public function withBody(string $expected_request_body): AssertRequestBuilder
    {
        $this->expected_request_body = $expected_request_body;

        return $this;
    }

    public function withHeader(string $header_name, string $expected_value): AssertRequestBuilder
    {
        $this->expected_headers[$header_name] = $expected_value;

        return $this;
    }

    public function withAuthorizationHeader(string $header_value): AssertRequestBuilder
    {
        return $this->withHeader('Authorization', $header_value);
    }

    public function withContentTypeJson(): AssertRequestBuilder
    {
        return $this->withHeader('Content-Type', 'application/json');
    }

    public function assert(): void
    {
        (new AssertRequest(
            $this->request,
            $this->expected_request_method,
            $this->expected_uri,
            $this->expected_request_body,
            $this->expected_headers,
        ))->assert();
    }
}
