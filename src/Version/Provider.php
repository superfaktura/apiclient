<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Version;

interface Provider
{
    public function getVersion(): string;
}
