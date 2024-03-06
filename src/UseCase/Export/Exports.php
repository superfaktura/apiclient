<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Export;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Contract\Export\Format;
use SuperFaktura\ApiClient\Response\BinaryResponse;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;

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
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function download(int $id): BinaryResponse
    {
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function exportInvoices(
        array $ids,
        Format $format,
        PdfExportOptions $pdf_options = new PdfExportOptions(),
    ): Response {
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }
}
