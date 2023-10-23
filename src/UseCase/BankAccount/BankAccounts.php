<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\BankAccount;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\BankAccount\BankAccountNotFoundException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotCreateBankAccountException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotDeleteBankAccountException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotUpdateBankAccountException;
use SuperFaktura\ApiClient\Contract\BankAccount\CannotGetAllBankAccountsException;

final readonly class BankAccounts implements Contract\BankAccount\BankAccounts
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function getAll(): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/bank_accounts/index',
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );

            if ($response->isError()) {
                throw new CannotGetAllBankAccountsException($request, $response->data['message'] ?? '');
            }

            return $response;
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetAllBankAccountsException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function create(array $bank_account): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/bank_accounts/add',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->transformBankAccountDataToJson($bank_account)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );

            if ($response->isError()) {
                throw new CannotCreateBankAccountException($request, $response->data['message'] ?? '');
            }

            return $response;
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotCreateBankAccountException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function update(int $id, array $bank_account): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/bank_accounts/update/' . $id,
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->transformBankAccountDataToJson($bank_account)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotUpdateBankAccountException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new BankAccountNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotUpdateBankAccountException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    public function delete(int $bank_account_id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/bank_accounts/delete/' . $bank_account_id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteBankAccountException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new BankAccountNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotDeleteBankAccountException($request, $response->data['error_message'] ?? '');
        }
    }

    /**
     * @param array<string, mixed> $bank_account
     *
     * @throws CannotCreateRequestException
     */
    private function transformBankAccountDataToJson(array $bank_account): string
    {
        try {
            return json_encode($bank_account, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
