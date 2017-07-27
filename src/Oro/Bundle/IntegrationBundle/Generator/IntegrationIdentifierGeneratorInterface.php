<?php

namespace Oro\Bundle\IntegrationBundle\Generator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface IntegrationIdentifierGeneratorInterface
{
    /**
     * @param Channel $channel
     * @return string
     */
    public function generateIdentifier(Channel $channel);
}
