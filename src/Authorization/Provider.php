<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Authorization;

interface Provider
{
    public function getAuthorization(): Authorization;
}
