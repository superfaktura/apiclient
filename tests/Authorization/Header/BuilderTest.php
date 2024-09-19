<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Authorization\Header;

use SuperFaktura\ApiClient\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Authorization\Header;
use SuperFaktura\ApiClient\Authorization\Authorization;

#[CoversClass(Header\Builder::class)]
final class BuilderTest extends \PHPUnit\Framework\TestCase
{
    private const MOCK_PACKAGE_VERSION = '2.0.0';

    /**
     * @return \Generator<array{expected: string, authorization: Authorization}>
     */
    public static function buildProvider(): \Generator
    {
        yield 'authorization' => [
            'expected' => 'SFAPI ' . http_build_query([
                'email' => 'test@example.com',
                'apikey' => 'cd114a5',
                'company_id' => 1,
                'module' => self::getModuleString('Test', 'Example s.r.o.'),
            ]),
            'authorization' => new Authorization(
                'test@example.com',
                'cd114a5',
                'Test',
                'Example s.r.o.',
                1,
            ),
        ];

        yield 'another authorization' => [
            'expected' => 'SFAPI ' . http_build_query([
                'email' => 'test2@example.com',
                'apikey' => 'a6b3f12',
                'company_id' => 2,
                'module' => self::getModuleString('API', 'Example2 s.r.o'),
            ]),
            'authorization' => new Authorization(
                'test2@example.com',
                'a6b3f12',
                'API',
                'Example2 s.r.o',
                2,
            ),
        ];
    }

    private static function getModuleString(string $module, string $app_title): string
    {
        return sprintf('%s [%s] (w/ SFAPI %s) [%s]', $module, $app_title, self::MOCK_PACKAGE_VERSION, PHP_VERSION_ID);
    }

    #[DataProvider('buildProvider')]
    public function testBuild(string $expected, Authorization $authorization): void
    {
        $fake_version_provider = $this->createMock(Version\Provider::class);
        $fake_version_provider->method('getVersion')->willReturn(self::MOCK_PACKAGE_VERSION);

        $builder = new Header\Builder($fake_version_provider);

        self::assertSame(
            expected: $expected,
            actual: $builder->build($authorization),
        );
    }
}
