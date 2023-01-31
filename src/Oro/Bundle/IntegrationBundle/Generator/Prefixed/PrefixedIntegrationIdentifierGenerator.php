<?php

namespace Oro\Bundle\IntegrationBundle\Generator\Prefixed;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

/**
 * Generates an integration identifier using unique prefix for each integration.
 */
class PrefixedIntegrationIdentifierGenerator implements IntegrationIdentifierGeneratorInterface
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function generateIdentifier(Channel $channel): string
    {
        return sprintf('%s_%s', $this->prefix, $channel->getId());
    }
}
