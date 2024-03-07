<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Export;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Export\Exports;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Contract\Export\ExportNotFoundException;
use SuperFaktura\ApiClient\UseCase\Export\InvoiceExportRequestFactory;
use SuperFaktura\ApiClient\Contract\Export\CannotGetExportStatusException;

#[CoversClass(Exports::class)]
#[UsesClass(Response::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(RequestException::class)]
final class ExportTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetStatus(): void
    {
        $fixture = __DIR__ . '/fixtures/get-status-success.json';

        $response = $this
            ->getExports($this->getHttpClientReturning($fixture))
            ->getStatus(1);

        $this->request()
            ->get('/exports/getStatus/1')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetStatusNotFound(): void
    {
        $this->expectException(ExportNotFoundException::class);

        $this
            ->getExports($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->getStatus(1);
    }

    public function testGetStatusInsufficientPermissions(): void
    {
        $this->expectException(CannotGetExportStatusException::class);

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getExports($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_UNAUTHORIZED))
            ->getStatus(1);
    }

    public function testGetStatusRequestFailed(): void
    {
        $this->expectException(CannotGetExportStatusException::class);

        $this
            ->getExports($this->getHttpClientWithMockRequestException())
            ->getStatus(1);
    }

    public function testGetStatusResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetExportStatusException::class);

        $this
            ->getExports($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getStatus(1);
    }

    protected function getExports(ClientInterface $client): Exports
    {
        return new Exports(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            invoice_export_request_factory: new InvoiceExportRequestFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
