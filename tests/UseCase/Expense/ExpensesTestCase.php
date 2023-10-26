<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Expense;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Expense\Expenses;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;

abstract class ExpensesTestCase extends TestCase
{
    protected const AUTHORIZATION_HEADER_VALUE = 'foo';

    protected function getExpenses(ClientInterface $client): Expenses
    {
        return new Expenses(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
