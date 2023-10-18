<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Stock;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Stock\Items;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateItemException;

#[CoversClass(Items::class)]
#[CoversClass(CannotCreateItemException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class ItemsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    private const BASE_URI = 'base_uri';

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

        $request = $this->getLastRequest();

        $expected_response_body = json_decode($response_body_json, true, 512, JSON_THROW_ON_ERROR);

        self::assertNotNull($request);
        self::assertPostRequest($request);
        self::assertSame($request_body, (string) $request->getBody());
        self::assertContentTypeJson($request);
        self::assertSame(self::BASE_URI . '/stock_items/add', $request->getUri()->getPath());
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

    private function getItems(ClientInterface $http_client): Items
    {
        return new Items(
            $http_client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            // no real requests are made during testing
            base_uri: self::BASE_URI,
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
