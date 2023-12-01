<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Authorization\Header;

use SuperFaktura\ApiClient\Version;
use SuperFaktura\ApiClient\Authorization\Authorization;

final readonly class Builder
{
    public function __construct(
        private Version\Provider $versionProvider,
    ) {
    }

    public function build(Authorization $authorization): string
    {
        return 'SFAPI ' . http_build_query([
            'email' => $authorization->email,
            'apikey' => $authorization->key,
            'company_id' => $authorization->company_id,
            'module' => $this->buildModule($authorization),
        ]);
    }

    private function buildModule(Authorization $authorization): string
    {
        return sprintf(
            '%s (w/ SFAPI %s) [%s]',
            $authorization->module,
            $this->versionProvider->getVersion(),
            PHP_VERSION_ID,
        );
    }
}
