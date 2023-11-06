<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Country;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Country\Countries;
use SuperFaktura\ApiClient\Contract\Country\CannotGetAllCountriesException;

#[CoversClass(Countries::class)]
#[UsesClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class CountriesTest extends TestCase
{
    private const AUTHORIZATION_HEADER_VALUE = 'foo';

    public function testGetAll(): void
    {
        $fixture = __DIR__ . '/fixtures/list.json';

        $response = $this
            ->getCountries($this->getHttpClientReturning($fixture))
            ->getAll();

        $this->request()
            ->get('/countries/index/view_full%3A1')
            ->withAuthorizationHeader(self::AUTHORIZATION_HEADER_VALUE)
            ->assert();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllCountriesException::class);

        $this
            ->getCountries($this->getHttpClientWithMockRequestException())
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllCountriesException::class);

        $this
            ->getCountries($this->getHttpClientWithMockResponse($this->getHttpOkResponseContainingInvalidJson()))
            ->getAll();
    }

    private function getCountries(ClientInterface $client): Countries
    {
        return new Countries(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            base_uri: '',
            authorization_header_value: self::AUTHORIZATION_HEADER_VALUE,
        );
    }
}
