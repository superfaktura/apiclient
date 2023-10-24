<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Money;

enum Currency: string
{
    case UNITED_ARAB_EMIRATES_DIRHAM = 'AED';

    case AFGHAN_AFGHANI = 'AFN';

    case ALBANIAN_LEK = 'ALL';

    case ARMENIAN_DRAM = 'AMD';

    case NETHERLANDS_ANTILLEAN_GUILDER = 'ANG';

    case ANGOLAN_KWANZA = 'AOA';

    case ARGENTINE_PESO = 'ARS';

    case AUSTRALIAN_DOLLAR = 'AUD';

    case ARUBAN_FLORIN = 'AWG';

    case AZERBAIJANI_MANAT = 'AZN';

    case BOSNIA_HERZEGOVINA_CONVERTIBLE_MARK = 'BAM';

    case BARBADIAN_DOLLAR = 'BBD';

    case BANGLADESHI_TAKA = 'BDT';

    case BULGARIAN_LEV = 'BGN';

    case BAHRAINI_DINAR = 'BHD';

    case BURUNDIAN_FRANC = 'BIF';

    case BERMUDAN_DOLLAR = 'BMD';

    case BRUNEI_DOLLAR = 'BND';

    case BOLIVIAN_BOLIVIANO = 'BOB';

    case BRAZILIAN_REAL = 'BRL';

    case BAHAMIAN_DOLLAR = 'BSD';

    case BHUTANESE_NGULTRUM = 'BTN';

    case BOTSWANAN_PULA = 'BWP';

    case BELARUSIAN_RUBLE = 'BYN';

    case BELIZE_DOLLAR = 'BZD';

    case CANADIAN_DOLLAR = 'CAD';

    case CONGOLESE_FRANC = 'CDF';

    case SWISS_FRANC = 'CHF';

    case CHILEAN_PESO = 'CLP';

    case CHINESE_YUAN = 'CNY';

    case COLOMBIAN_PESO = 'COP';

    case COSTA_RICAN_COLON = 'CRC';

    case CUBAN_PESO = 'CUP';

    case CAPE_VERDEAN_ESCUDO = 'CVE';

    case CZECH_REPUBLIC_KORUNA = 'CZK';

    case DJIBOUTIAN_FRANC = 'DJF';

    case DANISH_KRONE = 'DKK';

    case DOMINICAN_PESO = 'DOP';

    case ALGERIAN_DINAR = 'DZD';

    case EGYPTIAN_POUND = 'EGP';

    case ERITREAN_NAKFA = 'ERN';

    case ETHIOPIAN_BIRR = 'ETB';

    case EURO = 'EUR';

    case FIJIAN_DOLLAR = 'FJD';

    case FALKLAND_ISLANDS_POUND = 'FKP';

    case BRITISH_POUND_STERLING = 'GBP';

    case GEORGIAN_LARI = 'GEL';

    case GHANAIAN_CEDI = 'GHS';

    case GIBRALTAR_POUND = 'GIP';

    case GAMBIAN_DALASI = 'GMD';

    case GUINEAN_FRANC = 'GNF';

    case GUATEMALAN_QUETZAL = 'GTQ';

    case GUYANAESE_DOLLAR = 'GYD';

    case HONG_KONG_DOLLAR = 'HKD';

    case HONDURAN_LEMPIRA = 'HNL';

    case CROATIAN_KUNA = 'HRK';

    case HAITIAN_GOURDE = 'HTG';

    case HUNGARIAN_FORINT = 'HUF';

    case INDONESIAN_RUPIAH = 'IDR';

    case ISRAELI_NEW_SHEQEL = 'ILS';

    case INDIAN_RUPEE = 'INR';

    case IRAQI_DINAR = 'IQD';

    case IRANIAN_RIAL = 'IRR';

    case ICELANDIC_KRONA = 'ISK';

    case JAMAICAN_DOLLAR = 'JMD';

    case JORDANIAN_DINAR = 'JOD';

    case JAPANESE_YEN = 'JPY';

    case KENYAN_SHILLING = 'KES';

    case KYRGYSTANI_SOM = 'KGS';

    case CAMBODIAN_RIEL = 'KHR';

    case COMORIAN_FRANC = 'KMF';

    case NORTH_KOREAN_WON = 'KPW';

    case SOUTH_KOREAN_WON = 'KRW';

    case KUWAITI_DINAR = 'KWD';

    case CAYMAN_ISLANDS_DOLLAR = 'KYD';

    case KAZAKHSTANI_TENGE = 'KZT';

    case LAOTIAN_KIP = 'LAK';

    case LEBANESE_POUND = 'LBP';

    case SRI_LANKAN_RUPEE = 'LKR';

    case LIBERIAN_DOLLAR = 'LRD';

    case LESOTHO_LOTI = 'LSL';

    case LIBYAN_DINAR = 'LYD';

    case MOROCCAN_DIRHAM = 'MAD';

    case MOLDOVAN_LEU = 'MDL';

    case MALAGASY_ARIARY = 'MGA';

    case MACEDONIAN_DENAR = 'MKD';

    case MYANMA_KYAT = 'MMK';

    case MONGOLIAN_TUGRIK = 'MNT';

    case MACANESE_PATACA = 'MOP';

    case MAURITANIAN_OUGUIYA = 'MRU';

    case MAURITIAN_RUPEE = 'MUR';

    case MALDIVIAN_RUFIYAA = 'MVR';

    case MALAWIAN_KWACHA = 'MWK';

    case MEXICAN_PESO = 'MXN';

    case MALAYSIAN_RINGGIT = 'MYR';

    case MOZAMBICAN_METICAL = 'MZN';

    case NAMIBIAN_DOLLAR = 'NAD';

    case NIGERIAN_NAIRA = 'NGN';

    case NICARAGUAN_CORDOBA = 'NIO';

    case NORWEGIAN_KRONE = 'NOK';

    case NEPALESE_RUPEE = 'NPR';

    case NEW_ZEALAND_DOLLAR = 'NZD';

    case OMANI_RIAL = 'OMR';

    case PANAMANIAN_BALBOA = 'PAB';

    case PERUVIAN_NUEVO_SOL = 'PEN';

    case PAPUA_NEW_GUINEAN_KINA = 'PGK';

    case PHILIPPINE_PESO = 'PHP';

    case PAKISTANI_RUPEE = 'PKR';

    case POLISH_ZLOTY = 'PLN';

    case PARAGUAYAN_GUARANI = 'PYG';

    case QATARI_RIAL = 'QAR';

    case ROMANIAN_LEU = 'RON';

    case SERBIAN_DINAR = 'RSD';

    case RUSSIAN_RUBLE = 'RUB';

    case RWANDAN_FRANC = 'RWF';

    case SAUDI_RIYAL = 'SAR';

    case SOLOMON_ISLANDS_DOLLAR = 'SBD';

    case SEYCHELLOIS_RUPEE = 'SCR';

    case SUDANESE_POUND = 'SDG';

    case SWEDISH_KRONA = 'SEK';

    case SINGAPORE_DOLLAR = 'SGD';

    case SAINT_HELENA_POUND = 'SHP';

    case SIERRA_LEONEAN_LEONE = 'SLL';

    case SOMALI_SHILLING = 'SOS';

    case SURINAMESE_DOLLAR = 'SRD';

    case SOUTH_SUDANESE_POUND = 'SSP';

    case SAO_TOME_AND_PRINCIPE_DOBRA = 'STN';

    case SALVADORAN_COLON = 'SVC';

    case SYRIAN_POUND = 'SYP';

    case SWAZI_LILANGENI = 'SZL';

    case THAI_BAHT = 'THB';

    case TAJIKISTANI_SOMONI = 'TJS';

    case TURKMENISTANI_MANAT = 'TMT';

    case TUNISIAN_DINAR = 'TND';

    case TONGAN_PAANGA = 'TOP';

    case TURKISH_LIRA = 'TRY';

    case TRINIDAD_AND_TOBAGO_DOLLAR = 'TTD';

    case NEW_TAIWAN_DOLLAR = 'TWD';

    case TANZANIAN_SHILLING = 'TZS';

    case UKRAINIAN_HRYVNIA = 'UAH';

    case UGANDAN_SHILLING = 'UGX';

    case UNITED_STATES_DOLLAR = 'USD';

    case URUGUAYAN_PESO = 'UYU';

    case UZBEKISTAN_SOM = 'UZS';

    case VENEZUELAN_BOLIVAR_FUERTE = 'VEF';

    case VIETNAMESE_DONG = 'VND';

    case VANUATU_VATU = 'VUV';

    case SAMOAN_TALA = 'WST';

    case CFA_FRANC_BEAC = 'XAF';

    case EAST_CARIBBEAN_DOLLAR = 'XCD';

    case CFA_FRANC_BCEAO = 'XOF';

    case CFP_FRANC = 'XPF';

    case YEMENI_RIAL = 'YER';

    case SOUTH_AFRICAN_RAND = 'ZAR';

    case ZAMBIAN_KWACHA = 'ZMW';

    case ZIMBABWEAN_DOLLAR = 'ZWL';
}
