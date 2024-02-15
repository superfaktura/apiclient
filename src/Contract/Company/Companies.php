<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Company;

use SuperFaktura\ApiClient\Response\Response;

interface Companies
{
    /**
     * @throws CannotGetCurrentCompanyException
     */
    public function getCurrent(): Response;

    /**
     * @throws CannotGetAllCompaniesException
     */
    public function getAll(): Response;
}
