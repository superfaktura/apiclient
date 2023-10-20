<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\CashRegister;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Contract\CashRegister;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\CashRegister\Items;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

#[CoversClass(Items::class)]
#[CoversClass(CashRegister\CannotCreateCashRegisterItemException::class)]
#[CoversClass(RequestException::class)]
#[UsesClass(CannotCreateRequestException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class ItemsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    private const CASH_REGISTER_ID = 1;

    private const NON_EXISTENT_CASH_REGISTER = 2;

    /**
     * @throws \JsonException
     */
    public function testCreate(): void
    {
        $this->getUseCase($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->create(
                self::CASH_REGISTER_ID,
                [
                    'cash_register_id' => 321,
                    'amount' => 1.25,
                ],
            );

        $request = $this->getLastRequest();
        $expected_body = json_encode(['CashRegisterItem' => [
            'cash_register_id' => self::CASH_REGISTER_ID,
            'amount' => 1.25,
        ]], JSON_THROW_ON_ERROR);

        self::assertNotNull($request);
        self::assertSame(RequestMethodInterface::METHOD_POST, $request->getMethod());
        self::assertSame('/cash_register_items/add', $request->getUri()->getPath());
        self::assertSame($expected_body, (string) $request->getBody());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame(self::AUTHORIZATION_HEADER_VALUE, $request->getHeaderLine('Authorization'));
    }

    public function testCreateWithNonExistentCashRegister(): void
    {
        $this->expectException(CashRegister\CannotCreateCashRegisterItemException::class);

        $this->getUseCase($this->getHttpClientWithMockResponse($this->getNonExistentCashRegisterErrorResponse()))
            ->create(self::NON_EXISTENT_CASH_REGISTER, ['amount' => 1.25]);
    }

    public function testCreateWithInsufficientPermissions(): void
    {
        $this->expectException(CashRegister\CannotCreateCashRegisterItemException::class);

        $this->getUseCase($this->getHttpClientWithMockResponse($this->getInsufficientPermissionsResponse()))
            ->create(self::CASH_REGISTER_ID, ['amount' => 1.25]);
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CashRegister\CannotCreateCashRegisterItemException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->getUseCase($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->create(0, []);
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CashRegister\CannotCreateCashRegisterItemException::class);
        $this->getUseCase($this->getHttpClientWithMockRequestException())->create(0, []);
    }

    private function getUseCase(Client $client): CashRegister\Items
    {
        return new Items(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }

    private function getNonExistentCashRegisterErrorResponse(): ResponseInterface
    {
        $fixture = __DIR__ . '/fixtures/create-item-with-non-existent-cash-register.json';

        return new Response(StatusCodeInterface::STATUS_OK, [],
            $this->jsonFromFixture($fixture));
    }

    private function getInsufficientPermissionsResponse(): ResponseInterface
    {
        return new Response(StatusCodeInterface::STATUS_FORBIDDEN, [],
            $this->jsonFromFixture(__DIR__ . '/fixtures/create-item-insufficient-permissions.json'));
    }
}
