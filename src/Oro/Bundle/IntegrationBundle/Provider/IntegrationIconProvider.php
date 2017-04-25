<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class IntegrationIconProvider implements IntegrationIconProviderInterface
{
    /**
     * @var TypesRegistry
     */
    private $typesRegistry;

    /**
     * @param TypesRegistry $typesRegistry
     */
    public function __construct(TypesRegistry $typesRegistry)
    {
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(Channel $channel)
    {
        $types = $this->typesRegistry->getRegisteredChannelTypes();
        if (isset($types[$channel->getType()])) {
            $integration = $types[$channel->getType()];
            if ($integration instanceof IconAwareIntegrationInterface) {
                return $integration->getIcon();
            }
        }

        return '';
    }
}
