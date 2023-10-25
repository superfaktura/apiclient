<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract;

enum Language: string
{
    case CZECH = 'cze';

    case GERMAN = 'deu';

    case ENGLISH = 'eng';

    case CROATIAN = 'hrv';

    case HUNGARIAN = 'hun';

    case ITALIAN = 'ita';

    case DUTCH = 'nld';

    case POLISH = 'pol';

    case ROMANIAN = 'rom';

    case RUSSIAN = 'rus';

    case SLOVAK = 'slo';

    case SLOVENIAN = 'slv';

    case SPANISH = 'spa';

    case UKRAINIAN = 'ukr';
}
