<?php
namespace Oro\Bundle\IntegrationBundle\Provider;

interface PingableInterface
{
    /**
     * Check service availability.
     *
     * @return bool
     */
    public function ping();
}
