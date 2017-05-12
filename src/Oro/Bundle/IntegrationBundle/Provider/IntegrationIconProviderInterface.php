<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface IntegrationIconProviderInterface
{
    /**
     * @param Channel $channel
     *
     * @return string|null
     */
    public function getIcon(Channel $channel);
}
