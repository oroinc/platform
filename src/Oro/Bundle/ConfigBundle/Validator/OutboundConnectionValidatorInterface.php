<?php

namespace Oro\Bundle\ConfigBundle\Validator;

/**
 * Represents a service to check if outbound connections to external hosts and ports are permitted.
 */
interface OutboundConnectionValidatorInterface
{
    /**
     * Checks if outbound connections to the given hosts and ports are permitted.
     */
    public function isConnectionAllowed(string $host, int $port): bool;
}
