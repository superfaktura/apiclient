<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Authorization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Authorization\Authorization;
use SuperFaktura\ApiClient\Authorization\SimpleProvider;

#[CoversClass(SimpleProvider::class)]
#[CoversClass(Authorization::class)]
final class SimpleProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return \Generator<array{expected: Authorization}>
     */
    public static function getAuthorizationProvider(): \Generator
    {
        yield 'authorization' => [
            'expected' => new Authorization(
                email: 'test@example.com',
                key: 'a6b3f12',
                module: 'API',
                app_title: 'Example s.r.o.',
                company_id: 1,
            ),
            'email' => 'test@example.com',
            'key' => 'a6b3f12',
            'company_id' => 1,
            'app_title' => 'Example s.r.o.',
        ];

        yield 'another authorization' => [
            'expected' => new Authorization(
                email: 'another@example.com',
                key: 'e1bac132',
                module: 'API',
                app_title: 'Example2 s.r.o.',
                company_id: 2,
            ),
            'email' => 'another@example.com',
            'key' => 'e1bac132',
            'company_id' => 2,
            'app_title' => 'Example2 s.r.o.',
        ];
    }

    #[DataProvider('getAuthorizationProvider')]
    public function testGetAuthorization(
        Authorization $expected,
        string $email,
        string $key,
        int $company_id,
        string $app_title,
    ): void {
        $provider = new SimpleProvider($email, $key, $app_title, $company_id);
        self::assertEquals(expected: $expected, actual: $provider->getAuthorization());
    }
}
