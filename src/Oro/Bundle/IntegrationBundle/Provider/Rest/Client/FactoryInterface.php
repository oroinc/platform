<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\RestTransportSettingsInterface;

/**
 * Interface FactoryInterface
 */
interface FactoryInterface
{
    /**
     * Create REST client instance
     *
     * @param RestTransportSettingsInterface $transportEntity entity with connection settings
     *
     * @return RestClientInterface
     */
    public function getClientInstance(RestTransportSettingsInterface $transportEntity);
}
