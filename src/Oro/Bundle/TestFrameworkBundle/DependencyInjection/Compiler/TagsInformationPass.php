<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TagsInformationPass implements CompilerPassInterface
{
    const INFORMATION_PROVIDER_SERVICE = 'oro_test.provider.container_tags_documentation_information';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::INFORMATION_PROVIDER_SERVICE)) {
            $definition = $container->getDefinition(self::INFORMATION_PROVIDER_SERVICE);
            $definition->addMethodCall('setTags', [$container->findTags()]);
        }
    }
}
