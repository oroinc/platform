<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

interface DeleteProviderInterface
{
    /**
     * Is this provider supports given integration type
     *
     * @param string $type
     *
     * @return bool
     */
    public function supports($type);

    /**
     * Process delete of integration related data
     *
     * @param Integration $integration
     */
    public function deleteRelatedData(Integration $integration);
}
