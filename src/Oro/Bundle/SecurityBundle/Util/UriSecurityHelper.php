<?php

namespace Oro\Bundle\SecurityBundle\Util;

/**
 * Provides methods for checking and making safe URIs with dangerous protocols.
 */
class UriSecurityHelper
{
    /** @var array */
    private $allowedProtocols;

    public function __construct(array $allowedProtocols)
    {
        $this->allowedProtocols = array_map('strtolower', $allowedProtocols);
    }

    /**
     * Strips dangerous protocols from URI.
     */
    public function stripDangerousProtocols(string $uri): string
    {
        do {
            $originalUri = $uri;
            $protocol = $this->getProtocol($uri);
            if ($this->isDangerousProtocol($protocol)) {
                // Strips from the beginning the length of protocol + 1 (for colon : symbol).
                $uri = substr($uri, strlen($protocol) + 1);
            }
        } while ($uri !== $originalUri);

        return $uri;
    }

    /**
     * Checks if given URI has dangerous protocol.
     */
    public function uriHasDangerousProtocol(string $uri): bool
    {
        return $this->isDangerousProtocol($this->getProtocol($uri));
    }

    /**
     * Checks if given protocol is dangerous.
     */
    public function isDangerousProtocol(string $protocol): bool
    {
        return $protocol && !\in_array(strtolower($protocol), $this->getAllowedProtocols(), false);
    }

    public function getAllowedProtocols(): array
    {
        return $this->allowedProtocols;
    }

    private function getProtocol(string $uri): string
    {
        return strtolower((string)parse_url($uri, PHP_URL_SCHEME));
    }
}
