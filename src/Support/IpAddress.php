<?php

namespace PragmaRX\Firewall\Support;

use PragmaRX\Support\IpAddress as SupportIpAddress;

class IpAddress
{
    use ServiceInstances;

    /**
     * Check if IP address is valid.
     *
     * @param $ip
     *
     * @return bool
     */
    public function isValid($ip)
    {
        if (!$result = SupportIpAddress::ipV4Valid($ip = $this->hostToIp($ip)) ||
            $this->countries()->validCountry($ip)) {
            $this->messages()->addMessage(sprintf('%s is not a valid IP address', $ip));
        }

        return $result;
    }

    /**
     * Get the ip address of a host.
     *
     * @param $ip
     *
     * @return string
     */
    public function hostToIp($ip)
    {
        if (is_string($ip) && starts_with($ip, $string = 'host:')) {
            return gethostbyname(str_replace($string, '', $ip));
        }

        return $ip;
    }

    /**
     * Check if IP is in a valid range.
     *
     * @param $ip_address
     * @param $range
     *
     * @return bool
     */
    public function validRange($ip_address, $range)
    {
        return $this->config()->get('enable_range_search') &&
            SupportIpAddress::ipV4Valid($range->ip_address) &&
            SupportIpAddress::ipv4InRange($ip_address, $range->ip_address);
    }

    /**
     * Check if an ip v4 is valid.
     *
     * @param $item
     *
     * @return bool
     */
    public function ipV4Valid($item)
    {
        if (realpath($item) !== false) {
            return false;
        }

        return SupportIpAddress::ipV4Valid($item);
    }

    /**
     * Check if a string is a CIDR.
     *
     * @param $country
     *
     * @return bool|array
     */
    public function isCidr($country)
    {
        return SupportIpAddress::isCidr($country);
    }
}
