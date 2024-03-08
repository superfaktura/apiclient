<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Export;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use SuperFaktura\ApiClient\UseCase\Export\Exports;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Export\InvoiceExportRequestFactory;

abstract class ExportTestCase extends TestCase
{
    protected const AUTHORIZATION_HEADER_VALUE = 'foo';

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
