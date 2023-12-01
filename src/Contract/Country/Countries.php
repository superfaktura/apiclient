<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Country;

use SuperFaktura\ApiClient\Response\Response;

interface Countries
{
    /**
     * @throws CannotGetAllCountriesException
     */
    public function getAll(): Response;
}
