<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Stock;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Filter\Sort;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Stock\Items;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\UseCase\Stock\Movements;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Contract\Stock\ItemsQuery;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\Contract\Stock\ItemNotFoundException;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateItemException;
use SuperFaktura\ApiClient\Contract\Stock\CannotDeleteItemException;
use SuperFaktura\ApiClient\Contract\Stock\CannotUpdateItemException;
use SuperFaktura\ApiClient\Contract\Stock\CannotGetAllItemsException;
use SuperFaktura\ApiClient\Contract\Stock\CannotGetItemByIdException;

#[CoversClass(Items::class)]
#[CoversClass(CannotCreateItemException::class)]
#[CoversClass(RequestException::class)]
#[CoversClass(ItemsQuery::class)]
#[UsesClass(Movements::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(NamedParamsConvertor::class)]
final class ItemsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    private const BASE_URI = 'base_uri';

    private const DEFAULT_QUERY_PARAMS = ['listinfo' => 1];

    /**
     * @throws \JsonException
     */
    public function testCreate(): void
    {
        $data = [
            'StockItem' => [
                'name' => 'Rozok grahamovy',
                'sku'  => 'RZK-GRHMV',
                'unit_price' => 0.1,
                'purchase_unit_price' => 0.12,
            ],
        ];

        $request_body = json_encode($data, JSON_THROW_ON_ERROR);

        $fixture = __DIR__ . '/fixtures/create.json';
        $response_body_json = $this->jsonFromFixture($fixture);

        $use_case = $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
        ));
        $response = $use_case->create($data);

        $expected_response_body = json_decode($response_body_json, true, 512, JSON_THROW_ON_ERROR);

        $this->request()
            ->post(self::BASE_URI . '/stock_items/add')
            ->withBody($request_body)
            ->withContentTypeJson()
            ->assert();

        self::assertEquals($expected_response_body, $response->data);
    }

    public function testCreateInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateItemException::class);

        $fixture = __DIR__ . '/fixtures/create-insufficient-permissions.json';
        $use_case = $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ));
        $use_case->create([]);
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateItemException::class);
        $this->expectExceptionMessage('Syntax error');

        $use_case = $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()));
        $use_case->create([]);
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateItemException::class);
        $this->expectExceptionMessage(self::ERROR_COMMUNICATING_WITH_SERVER_MESSAGE);

        $use_case = $this->getItems($this->getHttpClientWithMockRequestException());
        $use_case->create([]);
    }

    public function testCreateWithNonValidJsonArray(): void
    {
        $this->expectException(CannotCreateRequestException::class);
        $this->expectExceptionMessage(self::JSON_ENCODE_FAILURE_MESSAGE);

        $use_case = $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponse()));
        $use_case->create(['StockItem' => ['name' => NAN]]);
    }

    public function testGetById(): void
    {
        $fixture = __DIR__ . '/fixtures/get-by-id.json';

        $response = $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
        ))->getById(1);

        $request = $this->getLastRequest();

        $expected_response_body = $this->arrayFromFixture($fixture);

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertSame(self::BASE_URI . '/stock_items/view/1', $request->getUri()->getPath());
        self::assertEquals($expected_response_body, $response->data);
    }

    public function testGetByIdNotFound(): void
    {
        $this->expectException(ItemNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/get-by-id-not-found.json';

        $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
        ))->getById(0);
    }

    public function testGetByIdRequestFailed(): void
    {
        $this->expectException(CannotGetItemByIdException::class);
        $this->getItems($this->getHttpClientWithMockRequestException())->getById(0);
    }

    public function testGetClientByIdResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetItemByIdException::class);
        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getById(0);
    }

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/stock-items.json';

        $response = $this->getItems($this->getHttpClientReturning($fixture))->getAll();
        $request = $this->getLastRequest();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);

        self::assertNotNull($request);
        self::assertSame(RequestMethodInterface::METHOD_GET, $request->getMethod());
        self::assertSame(self::BASE_URI . '/stock_items/index.json/listinfo%3A1', $request->getUri()->getPath());
        self::assertSame(self::AUTHORIZATION_HEADER_VALUE, $request->getHeaderLine('Authorization'));
    }

    public static function getAllQueryProvider(): \Generator
    {
        yield 'no filter specified, default query parameters' => [
            'expected_query_params' => [],
            'query' => new ItemsQuery(),
        ];

        yield 'sort by id descending' => [
            'expected_query_params' => ['sort' => 'id', 'direction' => 'DESC'],
            'query' => new ItemsQuery(sort: new Sort('id', SortDirection::DESC)),
        ];

        yield 'pagination support with limit' => [
            'expected_query_params' => ['page' => 2, 'per_page' => 10],
            'query' => new ItemsQuery(page: 2, per_page: 10),
        ];

        yield 'price from' => [
            'expected_query_params' => ['price_from' => 12.99],
            'query' => new ItemsQuery(price_from: 12.99),
        ];

        yield 'price to' => [
            'expected_query_params' => ['price_to' => 12.99],
            'query' => new ItemsQuery(price_to: 12.99),
        ];

        yield 'search' => [
            'expected_query_params' => ['search' => base64_encode('Rozok')],
            'query' => new ItemsQuery(search: 'Rozok'),
        ];

        yield 'sku' => [
            'expected_query_params' => ['sku' => base64_encode('RZK-GRHMV')],
            'query' => new ItemsQuery(sku: 'RZK-GRHMV'),
        ];

        yield 'status' => [
            'expected_query_params' => ['status' => 1],
            'query' => new ItemsQuery(status: 1),
        ];
    }

    /**
     * @param array<string, string|int|float> $expected_query_params
     */
    #[DataProvider('getAllQueryProvider')]
    public function testGetAllQuery(array $expected_query_params, ItemsQuery $query): void
    {
        $fixture = __DIR__ . '/fixtures/stock-items.json';
        $this->getItems($this->getHttpClientReturning($fixture))->getAll($query);

        $expected_uri = self::BASE_URI . '/stock_items/index.json/'
            . (new NamedParamsConvertor())->convert(array_merge(self::DEFAULT_QUERY_PARAMS, $expected_query_params));

        $this->request()
            ->get($expected_uri)
            ->assert();
    }

    public function testGetAllInternalServerError(): void
    {
        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this->expectException(CannotGetAllItemsException::class);

        $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, [], $this->jsonFromFixture($fixture)),
        ))->getAll();
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllItemsException::class);

        $this->getItems(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllItemsException::class);

        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getAll();
    }

    public function testUpdate(): void
    {
        $id = 1;
        $data = ['StockItem' => ['description' => 'new description']];
        $expected_body = json_encode($data, JSON_THROW_ON_ERROR);
        $fixture = __DIR__ . '/fixtures/update.json';

        $response = $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
        ))->update($id, $data);

        $this->request()
            ->patch(self::BASE_URI . '/stock_items/edit/' . $id)
            ->withBody($expected_body)
            ->withContentTypeJson()
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testUpdateNotFound(): void
    {
        $this->expectException(ItemNotFoundException::class);
        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))->update(123, []);
    }

    public function testUpdateInsufficientPermissions(): void
    {
        $this->expectException(CannotUpdateItemException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ))->update(0, []);
    }

    public function testUpdateInternalServerError(): void
    {
        $this->expectException(CannotUpdateItemException::class);
        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, [], $this->jsonFromFixture($fixture)),
        ))->update(0, []);
    }

    public function testUpdateResponseDecodeFailed(): void
    {
        $this->expectException(CannotUpdateItemException::class);
        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->update(0, []);
    }

    public function testUpdateRequestFailed(): void
    {
        $this->expectException(CannotUpdateItemException::class);
        $this->getItems($this->getHttpClientWithMockRequestException())->update(0, []);
    }

    public function testUpdateWithNonValidJsonArray(): void
    {
        $this->expectException(CannotCreateRequestException::class);
        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->update(0, ['StockItem' => ['name' => NAN]]);
    }

    public function testDelete(): void
    {
        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete(1);

        $this->request()
            ->delete(self::BASE_URI . '/stock_items/delete/1')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(ItemNotFoundException::class);

        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpNotFoundResponse()))
            ->delete(123);
    }

    public function testDeleteInsufficientPermissions(): void
    {
        $this->expectException(CannotDeleteItemException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';
        $this->getItems($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_FORBIDDEN, [], $this->jsonFromFixture($fixture)),
        ))->delete(0);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteItemException::class);
        $this->getItems($this->getHttpClientWithMockRequestException())->delete(0);
    }

    public function testDeleteResponseDecodeFailed(): void
    {
        $this->expectException(CannotDeleteItemException::class);
        $this->getItems($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->delete(0);
    }

    private function getItems(ClientInterface $http_client): Items
    {
        return new Items(
            $http_client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: self::BASE_URI,
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
