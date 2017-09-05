<?php

namespace PragmaRX\Firewall\Repositories;

use Illuminate\Support\Collection;
use PragmaRX\Firewall\Support\ServiceInstances;

class Countries
{
    use ServiceInstances;

    protected $all = [
        'ad'   => 'Andorra, Principality of',
        'ae'   => 'United Arab Emirates',
        'af'   => 'Afghanistan, Islamic State of',
        'ag'   => 'Antigua and Barbuda',
        'ai'   => 'Anguilla',
        'al'   => 'Albania',
        'am'   => 'Armenia',
        'an'   => 'Netherlands Antilles',
        'ao'   => 'Angola',
        'aq'   => 'Antarctica',
        'ar'   => 'Argentina',
        'arpa' => 'Old style Arpanet',
        'as'   => 'American Samoa',
        'at'   => 'Austria',
        'au'   => 'Australia',
        'aw'   => 'Aruba',
        'az'   => 'Azerbaidjan',
        'ba'   => 'Bosnia-Herzegovina',
        'bb'   => 'Barbados',
        'bd'   => 'Bangladesh',
        'be'   => 'Belgium',
        'bf'   => 'Burkina Faso',
        'bg'   => 'Bulgaria',
        'bh'   => 'Bahrain',
        'bi'   => 'Burundi',
        'bj'   => 'Benin',
        'bm'   => 'Bermuda',
        'bn'   => 'Brunei Darussalam',
        'bo'   => 'Bolivia',
        'br'   => 'Brazil',
        'bs'   => 'Bahamas',
        'bt'   => 'Bhutan',
        'bv'   => 'Bouvet Island',
        'bw'   => 'Botswana',
        'by'   => 'Belarus',
        'bz'   => 'Belize',
        'ca'   => 'Canada',
        'cc'   => 'Cocos (Keeling) Islands',
        'cf'   => 'Central African Republic',
        'cd'   => 'Congo, The Democratic Republic of the',
        'cg'   => 'Congo',
        'ch'   => 'Switzerland',
        'ci'   => 'Ivory Coast (Cote D\'Ivoire)',
        'ck'   => 'Cook Islands',
        'cl'   => 'Chile',
        'cm'   => 'Cameroon',
        'cn'   => 'China',
        'co'   => 'Colombia',
        'com'  => 'Commercial',
        'cr'   => 'Costa Rica',
        'cs'   => 'Former Czechoslovakia',
        'cu'   => 'Cuba',
        'cv'   => 'Cape Verde',
        'cx'   => 'Christmas Island',
        'cy'   => 'Cyprus',
        'cz'   => 'Czech Republic',
        'de'   => 'Germany',
        'dj'   => 'Djibouti',
        'dk'   => 'Denmark',
        'dm'   => 'Dominica',
        'do'   => 'Dominican Republic',
        'dz'   => 'Algeria',
        'ec'   => 'Ecuador',
        'edu'  => 'Educational',
        'ee'   => 'Estonia',
        'eg'   => 'Egypt',
        'eh'   => 'Western Sahara',
        'er'   => 'Eritrea',
        'es'   => 'Spain',
        'et'   => 'Ethiopia',
        'fi'   => 'Finland',
        'fj'   => 'Fiji',
        'fk'   => 'Falkland Islands',
        'fm'   => 'Micronesia',
        'fo'   => 'Faroe Islands',
        'fr'   => 'France',
        'fx'   => 'France (European Territory)',
        'ga'   => 'Gabon',
        'gb'   => 'Great Britain',
        'gd'   => 'Grenada',
        'ge'   => 'Georgia',
        'gf'   => 'French Guyana',
        'gh'   => 'Ghana',
        'gi'   => 'Gibraltar',
        'gl'   => 'Greenland',
        'gm'   => 'Gambia',
        'gn'   => 'Guinea',
        'gov'  => 'USA Government',
        'gp'   => 'Guadeloupe (French)',
        'gq'   => 'Equatorial Guinea',
        'gr'   => 'Greece',
        'gs'   => 'S. Georgia & S. Sandwich Isls.',
        'gt'   => 'Guatemala',
        'gu'   => 'Guam (USA)',
        'gw'   => 'Guinea Bissau',
        'gy'   => 'Guyana',
        'hk'   => 'Hong Kong',
        'hm'   => 'Heard and McDonald Islands',
        'hn'   => 'Honduras',
        'hr'   => 'Croatia',
        'ht'   => 'Haiti',
        'hu'   => 'Hungary',
        'id'   => 'Indonesia',
        'ie'   => 'Ireland',
        'il'   => 'Israel',
        'in'   => 'India',
        'int'  => 'International',
        'io'   => 'British Indian Ocean Territory',
        'iq'   => 'Iraq',
        'ir'   => 'Iran',
        'is'   => 'Iceland',
        'it'   => 'Italy',
        'jm'   => 'Jamaica',
        'jo'   => 'Jordan',
        'jp'   => 'Japan',
        'ke'   => 'Kenya',
        'kg'   => 'Kyrgyz Republic (Kyrgyzstan)',
        'kh'   => 'Cambodia, Kingdom of',
        'ki'   => 'Kiribati',
        'km'   => 'Comoros',
        'kn'   => 'Saint Kitts & Nevis Anguilla',
        'kp'   => 'North Korea',
        'kr'   => 'South Korea',
        'kw'   => 'Kuwait',
        'ky'   => 'Cayman Islands',
        'kz'   => 'Kazakhstan',
        'la'   => 'Laos',
        'lb'   => 'Lebanon',
        'lc'   => 'Saint Lucia',
        'li'   => 'Liechtenstein',
        'lk'   => 'Sri Lanka',
        'lr'   => 'Liberia',
        'ls'   => 'Lesotho',
        'lt'   => 'Lithuania',
        'lu'   => 'Luxembourg',
        'lv'   => 'Latvia',
        'ly'   => 'Libya',
        'ma'   => 'Morocco',
        'mc'   => 'Monaco',
        'md'   => 'Moldavia',
        'mg'   => 'Madagascar',
        'mh'   => 'Marshall Islands',
        'mil'  => 'USA Military',
        'mk'   => 'Macedonia',
        'ml'   => 'Mali',
        'mm'   => 'Myanmar',
        'mn'   => 'Mongolia',
        'mo'   => 'Macau',
        'mp'   => 'Northern Mariana Islands',
        'mq'   => 'Martinique (French)',
        'mr'   => 'Mauritania',
        'ms'   => 'Montserrat',
        'mt'   => 'Malta',
        'mu'   => 'Mauritius',
        'mv'   => 'Maldives',
        'mw'   => 'Malawi',
        'mx'   => 'Mexico',
        'my'   => 'Malaysia',
        'mz'   => 'Mozambique',
        'na'   => 'Namibia',
        'nato' => 'NATO (this was purged in 1996 - see hq.nato.int)',
        'nc'   => 'New Caledonia (French)',
        'ne'   => 'Niger',
        'net'  => 'Network',
        'nf'   => 'Norfolk Island',
        'ng'   => 'Nigeria',
        'ni'   => 'Nicaragua',
        'nl'   => 'Netherlands',
        'no'   => 'Norway',
        'np'   => 'Nepal',
        'nr'   => 'Nauru',
        'nt'   => 'Neutral Zone',
        'nu'   => 'Niue',
        'nz'   => 'New Zealand',
        'om'   => 'Oman',
        'org'  => 'Non-Profit Making Organisations (sic)',
        'pa'   => 'Panama',
        'pe'   => 'Peru',
        'pf'   => 'Polynesia (French)',
        'pg'   => 'Papua New Guinea',
        'ph'   => 'Philippines',
        'pk'   => 'Pakistan',
        'pl'   => 'Poland',
        'pm'   => 'Saint Pierre and Miquelon',
        'pn'   => 'Pitcairn Island',
        'pr'   => 'Puerto Rico',
        'pt'   => 'Portugal',
        'pw'   => 'Palau',
        'py'   => 'Paraguay',
        'qa'   => 'Qatar',
        're'   => 'Reunion (French)',
        'ro'   => 'Romania',
        'ru'   => 'Russian Federation',
        'rw'   => 'Rwanda',
        'sa'   => 'Saudi Arabia',
        'sb'   => 'Solomon Islands',
        'sc'   => 'Seychelles',
        'sd'   => 'Sudan',
        'se'   => 'Sweden',
        'sg'   => 'Singapore',
        'sh'   => 'Saint Helena',
        'si'   => 'Slovenia',
        'sj'   => 'Svalbard and Jan Mayen Islands',
        'sk'   => 'Slovak Republic',
        'sl'   => 'Sierra Leone',
        'sm'   => 'San Marino',
        'sn'   => 'Senegal',
        'so'   => 'Somalia',
        'sr'   => 'Suriname',
        'st'   => 'Saint Tome (Sao Tome) and Principe',
        'su'   => 'Former USSR',
        'sv'   => 'El Salvador',
        'sy'   => 'Syria',
        'sz'   => 'Swaziland',
        'tc'   => 'Turks and Caicos Islands',
        'td'   => 'Chad',
        'tf'   => 'French Southern Territories',
        'tg'   => 'Togo',
        'th'   => 'Thailand',
        'tj'   => 'Tadjikistan',
        'tk'   => 'Tokelau',
        'tm'   => 'Turkmenistan',
        'tn'   => 'Tunisia',
        'to'   => 'Tonga',
        'tp'   => 'East Timor',
        'tr'   => 'Turkey',
        'tt'   => 'Trinidad and Tobago',
        'tv'   => 'Tuvalu',
        'tw'   => 'Taiwan',
        'tz'   => 'Tanzania',
        'ua'   => 'Ukraine',
        'ug'   => 'Uganda',
        'uk'   => 'United Kingdom',
        'um'   => 'USA Minor Outlying Islands',
        'us'   => 'United States',
        'uy'   => 'Uruguay',
        'uz'   => 'Uzbekistan',
        'va'   => 'Holy See (Vatican City State)',
        'vc'   => 'Saint Vincent & Grenadines',
        've'   => 'Venezuela',
        'vg'   => 'Virgin Islands (British)',
        'vi'   => 'Virgin Islands (USA)',
        'vn'   => 'Vietnam',
        'vu'   => 'Vanuatu',
        'wf'   => 'Wallis and Futuna Islands',
        'ws'   => 'Samoa',
        'ye'   => 'Yemen',
        'yt'   => 'Mayotte',
        'yu'   => 'Yugoslavia',
        'za'   => 'South Africa',
        'zm'   => 'Zambia',
        'zr'   => 'Zaire',
        'zw'   => 'Zimbabwe',
    ];

    public function all()
    {
        return new Collection($this->all);
    }

    /**
     * Get the GeoIp instance.
     *
     * @return \PragmaRX\Support\GeoIp\GeoIp
     */
    public function getGeoIp()
    {
        return $this->geoIp();
    }

    public function isValid($cc)
    {
        $cc = strtolower(str_replace('country:', '', $cc));

        return $this->all()->has($cc);
    }

    /**
     * Get country code from an IP address.
     *
     * @param $ip_address
     *
     * @return string|null
     */
    public function getCountryFromIp($ip_address)
    {
        if ($geo = $this->geoIp()->searchAddr($ip_address)) {
            return strtolower($geo['country_code']);
        }
    }

    /**
     * Make a country info from a string.
     *
     * @param $country
     *
     * @return string
     */
    public function makeCountryFromString($country)
    {
        if ($ips = $this->ipAddress()->isCidr($country)) {
            $country = $ips[0];
        }

        if ($this->validCountry($country)) {
            return $country;
        }

        if ($this->dataRepository()->ipIsValid($country)) {
            $country = $this->getCountryFromIp($this->ipAddress()->hostToIp($country));
        }

        return "country:{$country}";
    }

    /**
     * Check if a string is a valid country info.
     *
     * @param $country
     *
     * @return bool
     */
    public function validCountry($country)
    {
        $country = strtolower($country);

        if ($this->config()->get('enable_country_search')) {
            if (starts_with($country, 'country:') && $this->isValid($country)) {
                return true;
            }
        }

        return false;
    }
}
