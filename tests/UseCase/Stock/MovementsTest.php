<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Stock;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Stock\Movements;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateMovementException;

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

    private function getMovements(ClientInterface $client): Movements
    {
        return new Movements(
            $client,
            new HttpFactory(),
            new ResponseFactory(),
            self::BASE_URI,
            self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
