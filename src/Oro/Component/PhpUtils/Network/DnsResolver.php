<?php

namespace Oro\Component\PhpUtils\Network;

/**
 * This class resolves ip address into hostname
 */
class DnsResolver
{
    /**
     * @param string $ipAddress
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getHostnameByIp(string $ipAddress): string
    {
        $result = \gethostbyaddr($ipAddress);
        if ($result === false) {
            throw new \InvalidArgumentException('Address is not a valid IPv4 or IPv6 address');
        }
        return $result;
    }
}
