<?php

namespace Oro\Bundle\IntegrationBundle\Generator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Represents a service that is used to generate an integration identifier.
 */
interface IntegrationIdentifierGeneratorInterface
{
    public function generateIdentifier(Channel $channel): string;
}
