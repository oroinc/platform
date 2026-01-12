<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

/**
 * Defines the contract for providers that handle deletion of integration-related data.
 *
 * Implementations of this interface are responsible for removing data associated with
 * a specific integration type when an integration channel is deleted. Each provider
 * handles cleanup of data specific to its integration type.
 */
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
     */
    public function deleteRelatedData(Integration $integration);
}
