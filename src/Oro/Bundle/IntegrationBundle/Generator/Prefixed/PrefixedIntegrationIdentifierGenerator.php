<?php

namespace Oro\Bundle\IntegrationBundle\Generator\Prefixed;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

class PrefixedIntegrationIdentifierGenerator implements IntegrationIdentifierGeneratorInterface
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function generateIdentifier(Channel $channel)
    {
        return sprintf('%s_%s', $this->prefix, $channel->getId());
    }
}
