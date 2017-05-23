<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Component\DependencyInjection\ServiceLink;

class IntegrationIconProvider implements IntegrationIconProviderInterface
{
    /**
     * @var ServiceLink
     */
    private $typesRegistryLink;

    /**
     * @param ServiceLink $typesRegistryLink
     */
    public function __construct(ServiceLink $typesRegistryLink)
    {
        $this->typesRegistryLink = $typesRegistryLink;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(Channel $channel)
    {
        $types = $this->getTypesRegistry()->getRegisteredChannelTypes();
        if (isset($types[$channel->getType()])) {
            $integration = $types[$channel->getType()];
            if ($integration instanceof IconAwareIntegrationInterface) {
                return $integration->getIcon();
            }
        }

        return null;
    }

    /**
     * @return TypesRegistry
     */
    private function getTypesRegistry()
    {
        return $this->typesRegistryLink->getService();
    }
}
