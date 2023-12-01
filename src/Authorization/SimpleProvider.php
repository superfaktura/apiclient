<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Authorization;

final readonly class SimpleProvider implements Provider
{
    public function __construct(
        private string $email,
        private string $key,
        private string $app_title,
        private int $company_id,
    ) {
    }

    public function getAuthorization(): Authorization
    {
        return new Authorization(
            $this->email,
            $this->key,
            'API',
            $this->app_title,
            $this->company_id,
        );
    }
}
