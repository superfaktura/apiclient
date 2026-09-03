<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Authorization;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\PathException;
use Symfony\Component\Dotenv\Exception\FormatException;

/**
 * @see \SuperFaktura\ApiClient\Test\Authorization\EnvFileProviderTest
 */
final readonly class EnvFileProvider implements Provider
{
    /**
     * @throws CannotLoadFileException
     */
    public function __construct(string $path)
    {
        try {
            $dotenv = new Dotenv();
            $dotenv->loadEnv($path, overrideExistingVars: true);
        } catch (FormatException|PathException $exception) {
            // Symfony's FormatException message embeds a raw excerpt of the .env file
            // around the syntax error, which may contain the API key. Keep the public
            // message free of file contents; the original stays available via getPrevious().
            throw new CannotLoadFileException(
                sprintf('Cannot load env file "%s".', $path),
                $exception->getCode(),
                $exception,
            );
        }
    }

    /**
     * @throws InvalidDotEnvConfigException
     */
    public function getAuthorization(): Authorization
    {
        return new Authorization(
            $this->getEnvByKey(DotEnvConfigKey::EMAIL) ?? throw new InvalidDotEnvConfigException(),
            $this->getEnvByKey(DotEnvConfigKey::KEY) ?? throw new InvalidDotEnvConfigException(),
            'API',
            $this->getEnvByKey(DotEnvConfigKey::APP_TITLE) ?? throw new InvalidDotEnvConfigException(),
            $this->getEnvByKey(DotEnvConfigKey::COMPANY_ID) !== null
                ? (int) $this->getEnvByKey(DotEnvConfigKey::COMPANY_ID)
                : throw new InvalidDotEnvConfigException(),
        );
    }

    private function getEnvByKey(string $key): ?string
    {
        $value = $_ENV[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
