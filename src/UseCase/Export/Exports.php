<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Export;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Contract\Export\Format;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use _PHPStan_11268e5ee\Fig\Http\Message\RequestMethodInterface;
use SuperFaktura\ApiClient\Response\CannotCreateResponseException;
use SuperFaktura\ApiClient\Contract\Export\ExportNotFoundException;
use SuperFaktura\ApiClient\Contract\Export\CannotDownloadExportException;
use SuperFaktura\ApiClient\Contract\Export\CannotGetExportStatusException;

final readonly class Exports implements Contract\Export\Exports
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private InvoiceExportRequestFactory $invoice_export_request_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function getStatus(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/exports/getStatus/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json');

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetExportStatusException($request, $e->getMessage(), $e->getCode(), $e);
        }

        return match ($response->status_code) {
            StatusCodeInterface::STATUS_OK => $response,
            StatusCodeInterface::STATUS_NOT_FOUND => throw new ExportNotFoundException($request),
            default => throw new CannotGetExportStatusException($request, $response->data['error_message'] ?? ''),
        };
    }

    public function download(int $id): BinaryResponse
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                sprintf('%s/exports/download_export/%d', $this->base_uri, $id),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromBinaryResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|CannotCreateResponseException $e) {
            throw new CannotDownloadExportException($request, $e->getMessage(), $e->getCode(), $e);
        }

        return match ($response->status_code) {
            StatusCodeInterface::STATUS_OK => $response,
            StatusCodeInterface::STATUS_NOT_FOUND => throw new ExportNotFoundException($request),
            default => throw new CannotDownloadExportException(
                $request,
                $this->getErrorMessageFromBinaryResponse($response),
            ),
        };
    }

    public function exportInvoices(
        array $ids,
        Format $format,
        PdfExportOptions $pdf_options = new PdfExportOptions(),
    ): Response {
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    private function getErrorMessageFromBinaryResponse(BinaryResponse $response): string
    {
        $text_body = stream_get_contents($response->data);

        if ($text_body === false) {
            return '';
        }

        try {
            /** @var array<string, string> $json_response */
            $json_response = json_decode($text_body, true, 512, JSON_THROW_ON_ERROR);

            return $json_response['error_message'] ?? '';
        } catch (\JsonException $e) {
            return $e->getMessage();
        }
    }
}
