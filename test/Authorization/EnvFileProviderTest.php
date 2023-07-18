<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Authorization;

use SuperFaktura\ApiClient\Authorization\Authorization;
use SuperFaktura\ApiClient\Authorization\DotEnvConfigKey;
use SuperFaktura\ApiClient\Authorization\EnvFileProvider;
use SuperFaktura\ApiClient\Authorization\CannotLoadFileException;
use SuperFaktura\ApiClient\Authorization\InvalidDotEnvConfigException;

final class EnvFileProviderTest extends \PHPUnit\Framework\TestCase
{
    private const NON_EXISTING_FILE = '.non-existing-file.env';

    private const INCOMPLETE_FILE = '.incomplete-mock.env';

    private const VALID_FILE = '.mock.env';

    private const ANOTHER_VALID_FILE = '.another-mock.env';

    /**
     * @return \Generator<array{expected: Authorization, path: string}>
     */
    public static function envFileDataProvider(): \Generator
    {
        yield 'file with complete config data' => [
            'expected' => new Authorization(
                email: 'test@example.com',
                key: 'test',
                module: 'API',
                app_title: 'Example s.r.o.',
                company_id: 1,
            ),
            'path' => __DIR__ . DIRECTORY_SEPARATOR . self::VALID_FILE,
        ];

        yield 'another file with complete config data' => [
            'expected' => new \SuperFaktura\ApiClient\Authorization\Authorization(
                email: 'test2@example.com',
                key: 'test2',
                module: 'API',
                app_title: 'Example2 s.r.o.',
                company_id: 2,
            ),
            'path' => __DIR__ . DIRECTORY_SEPARATOR . self::ANOTHER_VALID_FILE,
        ];
    }

    protected function setUp(): void
    {
        $this->clearEnvironment();
    }

    public function testWithMissingFile(): void
    {
        $this->expectException(CannotLoadFileException::class);
        new EnvFileProvider(__DIR__ . DIRECTORY_SEPARATOR . self::NON_EXISTING_FILE);
    }

    public function testWithIncompleteFile(): void
    {
        $this->expectException(InvalidDotEnvConfigException::class);
        $provider = new EnvFileProvider(__DIR__ . DIRECTORY_SEPARATOR . self::INCOMPLETE_FILE);
        $provider->getAuthorization();
    }

    /**
     * @dataProvider envFileDataProvider
     */
    public function testWithValidFile(Authorization $expected, string $path): void
    {
        self::assertEquals(
            expected: $expected,
            actual: (new EnvFileProvider($path))->getAuthorization(),
        );
    }

    /**
     * Clear all env variables accessed by provider
     */
    private function clearEnvironment(): void
    {
        unset(
            $_ENV[DotEnvConfigKey::EMAIL],
            $_ENV[DotEnvConfigKey::KEY],
            $_ENV[DotEnvConfigKey::APP_TITLE],
            $_ENV[DotEnvConfigKey::COMPANY_ID],
        );
    }
}
