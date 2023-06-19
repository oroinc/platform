<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Checks integration availability
 */
interface PingableInterface
{
    /**
     * Check service availability.
     *
     * @return bool
     */
    public function ping();
}
