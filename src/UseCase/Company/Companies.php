<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Company;

use Throwable;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Psr\Http\Message\RequestInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Contract\Company\CannotGetAllCompaniesException;
use SuperFaktura\ApiClient\Contract\Company\CannotGetCurrentCompanyException;

final readonly class Companies implements Contract\Company\Companies
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function getCurrent(): Response
    {
        return $this->getCompanies();
    }

    public function getAll(): Response
    {
        return $this->getCompanies(true);
    }

    /**
     * @throws CannotGetAllCompaniesException | CannotGetCurrentCompanyException
     */
    private function getCompanies(bool $all = false): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/users/getUserCompaniesData' . ($all ? '/1' : ''),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );

            if ($response->isError()) {
                throw $this->getRequestException($all, $request, $response->data['message'] ?? '');
            }

            return $response;
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw $this->getRequestException($all, $request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getRequestException(bool $all, RequestInterface $req, string $message, int $code = 0, Throwable $previous = null): RequestException
    {
        return $all
            ? new CannotGetAllCompaniesException($req, $message, $code, $previous)
            : new CannotGetCurrentCompanyException($req, $message, $code, $previous);
    }
}
