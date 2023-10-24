<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\BankAccount;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\BankAccount\BankAccounts;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\BankAccount\BankAccountNotFoundException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotCreateBankAccountException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotDeleteBankAccountException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotUpdateBankAccountException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotGetAllBankAccountsException;

#[CoversClass(BankAccounts::class)]
#[CoversClass(BankAccountNotFoundException::class)]
#[CoversClass(CannotGetAllBankAccountsException::class)]
#[CoversClass(CannotCreateBankAccountException::class)]
#[CoversClass(CannotUpdateBankAccountException::class)]
#[CoversClass(CannotDeleteBankAccountException::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(CannotCreateRequestException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class BankAccountsTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/multiple-bank-accounts.json';

        $response = $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAll();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllInternalServerError(): void
    {
        $this->expectException(CannotGetAllBankAccountsException::class);
        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this->getBankAccounts($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, [], $this->jsonFromFixture($fixture)),
        ))->getAll();
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllBankAccountsException::class);

        $this
            ->getBankAccounts($this->getHttpClientWithMockRequestException())
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllBankAccountsException::class);

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{'),
            ),
        )
            ->getAll();
    }

    public function testDelete(): void
    {
        $this
            ->getBankAccounts($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->delete(1);

        $this->request()
            ->delete('/bank_accounts/delete/1')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(BankAccountNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    public function testDeleteInsufficientPermissions(): void
    {
        $this->expectException(CannotDeleteBankAccountException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->delete(1);
    }

    public function testDeleteRequestFailed(): void
    {
        $this->expectException(CannotDeleteBankAccountException::class);

        $this
            ->getBankAccounts($this->getHttpClientWithMockRequestException())
            ->delete(1);
    }

    public function testDeleteResponseDecodeFailed(): void
    {
        $this->expectException(CannotDeleteBankAccountException::class);
        $this->getBankAccounts($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->delete(0);
    }

    /**
     * @return \Generator<array{data: array<string, mixed>, request_body: string}>
     */
    public static function createProvider(): \Generator
    {
        $data = [
            'bank_name' => 'Tatra banka, a.s.',
            'iban' => 'SK3211000000002926858237',
            'swift' => 'TATRSKBX',
            'default' => 1,
            'show' => 1,
            'show_account' => 1,
        ];

        yield 'bank account is created' => [
            'data' => $data,
            'request_body' => (string) json_encode($data),
        ];

        $data = [
            'iban' => 'SK3211000000002926858238',
        ];

        yield 'bank account with minimal data is created' => [
            'data' => $data,
            'request_body' => (string) json_encode($data),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('createProvider')]
    public function testCreate(array $data, string $request_body): void
    {
        $this
            ->getBankAccounts($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->create($data);

        $this->request()
            ->post('/bank_accounts/add')
            ->withBody($request_body)
            ->withContentTypeJson()
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testCreateInsufficientPermissions(): void
    {
        $this->expectException(CannotCreateBankAccountException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->create([]);
    }

    public function testCreateResponseDecodeFailed(): void
    {
        $this->expectException(CannotCreateBankAccountException::class);
        $this->getBankAccounts($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->create([]);
    }

    public function testCreateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(),
        )
            ->create(['bank_name' => NAN]);
    }

    public function testCreateRequestFailed(): void
    {
        $this->expectException(CannotCreateBankAccountException::class);
        $this->getBankAccounts($this->getHttpClientWithMockRequestException())->create([]);
    }

    /**
     * @return \Generator<array{id: int, data: array<string, mixed>, request_body: string}>
     */
    public static function updateProvider(): \Generator
    {
        $data = [
            'bank_name' => 'Tatra banka, a.s.',
        ];

        yield 'bank account is updated' => [
            'id' => 1,
            'data' => $data,
            'request_body' => (string) json_encode($data),
        ];

        $data = [
            'iban' => 'SK3211000000002926858238',
        ];

        yield 'another bank account is updated' => [
            'id' => 2,
            'data' => $data,
            'request_body' => (string) json_encode($data),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('updateProvider')]
    public function testUpdate(int $id, array $data, string $request_body): void
    {
        $this
            ->getBankAccounts($this->getHttpClientWithMockResponse($this->getHttpOkResponse()))
            ->update($id, $data);

        $this->request()
            ->post('/bank_accounts/update/' . $id)
            ->withBody($request_body)
            ->withContentTypeJson()
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();
    }

    public function testUpdateNotFound(): void
    {
        $this->expectException(BankAccountNotFoundException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_NOT_FOUND, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->update(1, []);
    }

    public function testUpdateInsufficientPermissions(): void
    {
        $this->expectException(CannotUpdateBankAccountException::class);

        $fixture = __DIR__ . '/fixtures/insufficient-permissions.json';

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->update(1, []);
    }

    public function testUpdateResponseDecodeFailed(): void
    {
        $this->expectException(CannotUpdateBankAccountException::class);
        $this->getBankAccounts($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->update(1, []);
    }

    public function testUpdateInvalidRequestData(): void
    {
        $this->expectException(CannotCreateRequestException::class);

        $this->getBankAccounts(
            $this->getHttpClientWithMockResponse(),
        )
            ->update(1, ['bank_name' => NAN]);
    }

    public function testUpdateRequestFailed(): void
    {
        $this->expectException(CannotUpdateBankAccountException::class);
        $this->getBankAccounts($this->getHttpClientWithMockRequestException())->update(0, []);
    }

    private function getBankAccounts(Client $client): BankAccounts
    {
        return new BankAccounts(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
