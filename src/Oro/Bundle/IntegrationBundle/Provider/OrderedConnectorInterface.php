<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Allows to customize order of processing of integration's connectors.
 */
interface OrderedConnectorInterface extends ConnectorInterface
{
    /**
     * Default order of connector
     */
    const DEFAULT_ORDER = 0;

    /**
     * Get the order of this connector. Connectors with lesser value will be processed first.
     * Default order for connectors which not implements this interface should be 0.
     *
     * @return integer
     */
    public function getOrder();
}
