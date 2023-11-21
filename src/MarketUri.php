<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient;

enum MarketUri: string
{
    case SLOVAK = 'https://moja.superfaktura.sk';

    case CZECH = 'https://moje.superfaktura.cz';

    case AUSTRIAN = 'https://meine.superfaktura.at';

    case SLOVAK_SANDBOX = 'https://sandbox.superfaktura.sk';

    case CZECH_SANDBOX = 'https://sandbox.superfaktura.cz';
}
