<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Export;

use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Export\Exports;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Contract\Export\ExportNotFoundException;
use SuperFaktura\ApiClient\Contract\Export\CannotDownloadExportException;
use SuperFaktura\ApiClient\Contract\Export\CannotGetExportStatusException;

#[CoversClass(Exports::class)]
#[UsesClass(Response::class)]
#[UsesClass(BinaryResponse::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(RequestException::class)]
final class ExportTest extends ExportTestCase
{
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

    public function testDownload(): void
    {
        $fixture = __DIR__ . '/../../Response/fixtures/foo.pdf';
        $response = $this->getExports(
            $this->getHttpClientWithMockResponse(
                self::getPsrBinaryResponse($fixture, StatusCodeInterface::STATUS_OK),
            ),
        )
            ->download(1);

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame('/exports/download_export/1', $request->getUri()->getPath());
        self::assertStringEqualsFile($fixture, (string) stream_get_contents($response->data));
    }

    public function testDownloadNotFound(): void
    {
        $this->expectException(ExportNotFoundException::class);

        $this
            ->getExports($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->download(1);
    }

    public function testDownloadInsufficientPermissions(): void
    {
        $this->expectException(CannotDownloadExportException::class);

        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this
            ->getExports($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_UNAUTHORIZED))
            ->download(1);
    }

    public function testDownloadRequestFailed(): void
    {
        $this->expectException(CannotDownloadExportException::class);

        $this
            ->getExports($this->getHttpClientWithMockRequestException())
            ->download(1);
    }
}
