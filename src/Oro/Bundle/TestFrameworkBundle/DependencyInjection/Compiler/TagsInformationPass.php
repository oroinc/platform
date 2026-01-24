<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects and provides container tag information for documentation purposes.
 *
 * This compiler pass gathers all service tags defined in the container and makes them available
 * to the tags documentation information provider for generating documentation or providing IDE support.
 */
class TagsInformationPass implements CompilerPassInterface
{
    const INFORMATION_PROVIDER_SERVICE = 'oro_test.provider.container_tags_documentation_information';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::INFORMATION_PROVIDER_SERVICE)) {
            $definition = $container->getDefinition(self::INFORMATION_PROVIDER_SERVICE);
            $definition->addMethodCall('setTags', [$container->findTags()]);
        }
    }
}
