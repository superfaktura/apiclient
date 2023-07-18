<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Version;

final class ComposerProvider implements Provider
{
    private const UNKNOWN_VERSION = 'unknown';

    public function getVersion(): string
    {
        return \Composer\InstalledVersions::getPrettyVersion('superfaktura/apiclient')
            ?? self::UNKNOWN_VERSION;
    }
}
