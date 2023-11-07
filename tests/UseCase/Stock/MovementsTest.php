<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Stock;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Filter\Sort;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\UseCase\Stock\Movements;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\Contract\Stock\MovementsQuery;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateMovementException;
use SuperFaktura\ApiClient\Contract\Stock\CannotGetAllMovementsException;

#[CoversClass(Movements::class)]
#[CoversClass(CannotCreateMovementException::class)]
#[CoversClass(RequestException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class MovementsTest extends TestCase
{
    private const BASE_URI = 'base-uri';

    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    private const EMPTY_DATA = ['StockLog' => []];

    public function testCreate(): void
    {
        $fixture = __DIR__ . '/fixtures/create-movement.json';
        $response = $this->getMovements($this->getHttpClientReturning($fixture))
            ->create(1, ['StockLog' => [
                ['quantity' => 5, 'note' => 'Hungry binge shopping at kaufland'],
            ]]);

        $expected_body_json = json_encode(
            ['StockLog' => [['quantity' => 5, 'stock_item_id' => 1, 'note' => 'Hungry binge shopping at kaufland']]],
            JSON_THROW_ON_ERROR,
        );

        $this->request()
            ->post(self::BASE_URI . '/stock_items/addStockMovement')
            ->withBody($expected_body_json)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertEquals($this->arrayFromFixture($fixture), $response->data);
    }

    public function testCreateInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateMovementException::class);

        $fixture = __DIR__ . '/fixtures/create-insufficient-permissions.json';

        $this->getMovements($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_FORBIDDEN))
            ->create(0, self::EMPTY_DATA);
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateMovementException::class);

        $this->getMovements($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->create(0, self::EMPTY_DATA);
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateMovementException::class);
        $this->getMovements($this->getHttpClientWithMockRequestException())->create(0, self::EMPTY_DATA);
    }

    public function testCreateWithNonValidJsonArray(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getMovements($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->create(0, ['StockLog' => [['quantity' => NAN]]]);
    }

    public function testCreateWithSku(): void
    {
        $fixture = __DIR__ . '/fixtures/create-movement.json';
        $response = $this->getMovements($this->getHttpClientReturning($fixture))
            ->createWithSku('RZK-GRHMV', ['StockLog' => [
                ['quantity' => 5, 'note' => 'Hungry binge shopping at kaufland'],
            ]]);

        $expected_body_json = json_encode(
            ['StockLog' => [['quantity' => 5, 'sku' => 'RZK-GRHMV', 'note' => 'Hungry binge shopping at kaufland']]],
            JSON_THROW_ON_ERROR,
        );

        $this->request()
            ->post(self::BASE_URI . '/stock_items/addStockMovement')
            ->withBody($expected_body_json)
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertEquals($this->arrayFromFixture($fixture), $response->data);
    }

    public function testCreateWithSkuInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateMovementException::class);

        $fixture = __DIR__ . '/fixtures/create-insufficient-permissions.json';

        $this->getMovements($this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_FORBIDDEN))
            ->createWithSku('', self::EMPTY_DATA);
    }

    public function testCreateWithSkuResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateMovementException::class);

        $this->getMovements($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->createWithSku('', self::EMPTY_DATA);
    }

    public function testCreateWithSkuRequestFailed(): void
    {
        $this->expectException(CannotCreateMovementException::class);
        $this->getMovements($this->getHttpClientWithMockRequestException())->create(0, self::EMPTY_DATA);
    }

    public function testCreateWithSkuWithNonValidJsonArray(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getMovements($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->createWithSku('', ['StockLog' => [['quantity' => NAN]]]);
    }

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/stock-movements.json';
        $id = 1;
        $response = $this->getMovements($this->getHttpClientReturning($fixture))->getAll($id);

        $this->request()
            ->get(self::BASE_URI . '/stock_items/movements/' . $id . '/')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public static function getAllQueryProvider(): \Generator
    {
        yield 'no filter specified, default query parameters' => [
            'expected_query_params' => [],
            'query' => new MovementsQuery(),
        ];

        yield 'sort by id descending' => [
            'expected_query_params' => ['sort' => 'id', 'direction' => 'DESC'],
            'query' => new MovementsQuery(sort: new Sort('id', SortDirection::DESC)),
        ];

        yield 'pagination support with limit' => [
            'expected_query_params' => ['page' => 2, 'per_page' => 10],
            'query' => new MovementsQuery(page: 2, per_page: 10),
        ];
    }

    /**
     * @param array<string, int|string> $expected_query_params
     */
    #[DataProvider('getAllQueryProvider')]
    public function testGetAllQuery(array $expected_query_params, MovementsQuery $query): void
    {
        $this->getMovements($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))->getAll(1, $query);
        $expected_uri = self::BASE_URI . '/stock_items/movements/1/'
            . (new NamedParamsConvertor())->convert($expected_query_params);

        $this->request()
            ->get($expected_uri)
            ->assert();
    }

    public function testGetAllInternalServerError(): void
    {
        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';
        $this->expectException(CannotGetAllMovementsException::class);

        $this->getMovements(
            $this->getHttpClientReturning($fixture, StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR),
        )->getAll(0);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllMovementsException::class);
        $this->getMovements($this->getHttpClientWithMockRequestException())->getAll(0);
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllMovementsException::class);

        $this->getMovements($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getAll(0);
    }

    private function getMovements(ClientInterface $client): Movements
    {
        return new Movements(
            $client,
            new HttpFactory(),
            new ResponseFactory(),
            new NamedParamsConvertor(),
            self::BASE_URI,
            self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
