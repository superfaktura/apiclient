<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\CashRegister;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\CashRegister\CashRegisters;
use SuperFaktura\ApiClient\Contract\CashRegister\CannotGetAllCashRegistersException;

#[CoversClass(CashRegisters::class)]
#[CoversClass(CannotGetAllCashRegistersException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class CashRegistersTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/getAll-multiple-items.json';

        $response = $this->getCashRegisters(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )->getAll();

        $request = $this->getLastRequest();

        self::assertNotNull($request);
        self::assertGetRequest($request);
        self::assertSame('/cash_registers/getDetails', $request->getUri()->getPath());
        self::assertAuthorizationHeader($request, self::AUTHORIZATION_HEADER_VALUE);
        self::assertSame(self::AUTHORIZATION_HEADER_VALUE, $request->getHeaderLine('Authorization'));
        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllCashRegistersException::class);
        $this->expectExceptionMessage(self::ERROR_COMMUNICATING_WITH_SERVER_MESSAGE);
        $this->getCashRegisters($this->getHttpClientWithMockRequestException())->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllCashRegistersException::class);
        $this->expectExceptionMessage('Syntax error');
        $this->getCashRegisters($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getAll();
    }

    private function getCashRegisters(Client $client): CashRegisters
    {
        return new CashRegisters(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
