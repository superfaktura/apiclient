<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Company;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Company\Companies;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Company\CannotGetAllCompaniesException;
use SuperFaktura\ApiClient\Contract\Company\CannotGetCurrentCompanyException;

#[CoversClass(Companies::class)]
#[CoversClass(CannotGetCurrentCompanyException::class)]
#[CoversClass(CannotGetAllCompaniesException::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(CannotCreateRequestException::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RateLimit::class)]
final class CompaniesTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetCurrent(): void
    {
        $fixture = __DIR__ . '/fixtures/current-company.json';
        $response = $this->getCompanies(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )->getCurrent();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetCurrentCompanyException(): void
    {
        $this->expectException(CannotGetCurrentCompanyException::class);
        $this->getCompanies($this->getHttpClientWithMockRequestException())->getCurrent();
    }

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/all-companies.json';
        $response = $this->getCompanies(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )->getAll();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllInternalServerError(): void
    {
        $this->expectException(CannotGetAllCompaniesException::class);
        $fixture = __DIR__ . '/../fixtures/unexpected-error.json';

        $this->getCompanies($this->getHttpClientWithMockResponse(
            new Response(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, [], $this->jsonFromFixture($fixture)),
        ))->getAll();
    }

    public function testGetAllCompaniesException(): void
    {
        $this->expectException(CannotGetAllCompaniesException::class);
        $this->getCompanies($this->getHttpClientWithMockRequestException())->getAll();
    }

    private function getCompanies(Client $client): Companies
    {
        return new Companies(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
