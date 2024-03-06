<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Invoice;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;

abstract class InvoicesTestCase extends TestCase
{
    protected const AUTHORIZATION_HEADER_VALUE = 'foo';

    /**
     * @return \Generator<int[]>
     */
    public static function invoiceIdProvider(): \Generator
    {
        yield 'invoice' => [1];
        yield 'another invoice' => [2];
    }

    protected function getInvoices(ClientInterface $client): Invoices
    {
        return new Invoices(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
