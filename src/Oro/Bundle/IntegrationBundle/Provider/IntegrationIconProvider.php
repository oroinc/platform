<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provides icons for integration channels based on their type.
 *
 * This provider retrieves the appropriate icon for an integration channel by looking up
 * the channel's type in the types registry and checking if the type implements the
 * IconAwareIntegrationInterface. If an icon is available, it returns the icon path;
 * otherwise, it returns null.
 */
class IntegrationIconProvider implements IntegrationIconProviderInterface
{
    /**
     * @var ServiceLink
     */
    private $typesRegistryLink;

    public function __construct(ServiceLink $typesRegistryLink)
    {
        $this->typesRegistryLink = $typesRegistryLink;
    }

    #[\Override]
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
