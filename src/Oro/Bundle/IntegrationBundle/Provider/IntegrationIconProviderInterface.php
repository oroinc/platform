<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Defines the contract for providers that retrieve icons for integration channels.
 *
 * Implementations of this interface are responsible for providing the appropriate icon
 * for a given integration channel, which can be used in the UI to visually represent
 * the integration type.
 */
interface IntegrationIconProviderInterface
{
    /**
     * @param Channel $channel
     *
     * @return string|null
     */
    public function getIcon(Channel $channel);
}
