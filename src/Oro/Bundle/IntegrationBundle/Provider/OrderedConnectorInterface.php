<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

interface OrderedConnectorInterface
{
    /**
     * Get the order of this connector
     *
     * @return integer
     */
    public function getOrder();
}
