<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\Authorization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Authorization\Authorization;
use Symfony\Component\Dotenv\Exception\FormatException;
use SuperFaktura\ApiClient\Authorization\DotEnvConfigKey;
use SuperFaktura\ApiClient\Authorization\EnvFileProvider;
use SuperFaktura\ApiClient\Authorization\CannotLoadFileException;
use SuperFaktura\ApiClient\Authorization\InvalidDotEnvConfigException;

#[CoversClass(EnvFileProvider::class)]
#[CoversClass(Authorization::class)]
final class EnvFileProviderTest extends \PHPUnit\Framework\TestCase
{
    private const NON_EXISTING_FILE = '.non-existing-file.env';

    private const INCOMPLETE_FILE = '.incomplete-mock.env';

    private const VALID_FILE = '.mock.env';

    private const ANOTHER_VALID_FILE = '.another-mock.env';

    private const MALFORMED_FILE = '.malformed-mock.env';

    /**
     * Kept short on purpose: Symfony's FormatException quotes only ~20 characters
     * around the syntax error, so a longer value would be truncated and the
     * assertion would pass even against a leaking implementation.
     */
    private const MALFORMED_FILE_SECRET = 'leaked-key';

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

    public function testMalformedFileExceptionMessageDoesNotExposeFileContents(): void
    {
        try {
            new EnvFileProvider(__DIR__ . DIRECTORY_SEPARATOR . self::MALFORMED_FILE);

            self::fail(CannotLoadFileException::class . ' was not thrown.');
        } catch (CannotLoadFileException $cannotLoadFileException) {
            self::assertStringNotContainsString(
                self::MALFORMED_FILE_SECRET,
                $cannotLoadFileException->getMessage(),
            );
            self::assertInstanceOf(FormatException::class, $cannotLoadFileException->getPrevious());
        }
    }

    public function testWithIncompleteFile(): void
    {
        $this->expectException(InvalidDotEnvConfigException::class);
        $provider = new EnvFileProvider(__DIR__ . DIRECTORY_SEPARATOR . self::INCOMPLETE_FILE);
        $provider->getAuthorization();
    }

    #[DataProvider('envFileDataProvider')]
    public function testWithValidFile(Authorization $expected, string $path): void
    {
        self::assertEquals(
            $expected,
            (new EnvFileProvider($path))->getAuthorization(),
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
