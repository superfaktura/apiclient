<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\UseCase\Stock;
use SuperFaktura\ApiClient\UseCase\Tag\Tags;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\UseCase\Client\Clients;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Expense\Expenses;
use SuperFaktura\ApiClient\UseCase\Invoice\Invoices;
use SuperFaktura\ApiClient\Version\ComposerProvider;
use SuperFaktura\ApiClient\UseCase\Country\Countries;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\UseCase\BankAccount\BankAccounts;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\UseCase\CashRegister\CashRegisters;
use SuperFaktura\ApiClient\UseCase\Invoice\ExportRequestFactory;
use SuperFaktura\ApiClient\UseCase\RelatedDocument\RelatedDocuments;

final readonly class ApiClient
{
    public BankAccounts $bank_accounts;

    public Clients $clients;

    public CashRegisters $cash_registers;

    public Countries $countries;

    public Invoices $invoices;

    public Expenses $expenses;

    public RelatedDocuments $related_documents;

    public Stock\Items $stock_items;

    public Tags $tags;

    public function __construct(
        private Authorization\Provider $authorization_provider,
        MarketUri|string $base_uri,
        private ClientInterface $http_client = new Client(),
        private RequestFactoryInterface $request_factory = new HttpFactory(),
        private ResponseFactoryInterface $response_factory = new ResponseFactory(),
    ) {
        $base_uri = is_string($base_uri) ? $base_uri : $base_uri->value;

        $authorization_header_value = (new Authorization\Header\Builder(new ComposerProvider()))
            ->build($this->authorization_provider->getAuthorization());

        $this->bank_accounts = new BankAccounts(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->clients = new Clients(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->cash_registers = new CashRegisters(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->countries = new Countries(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->invoices = new Invoices(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            query_params_convertor: new NamedParamsConvertor(),
            export_request_factory: new ExportRequestFactory(),
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->expenses = new Expenses(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->related_documents = new RelatedDocuments(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->stock_items = new Stock\Items(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );

        $this->tags = new Tags(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $base_uri,
            authorization_header_value: $authorization_header_value,
        );
    }
}
