<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ResourceMatcherVotersPass implements CompilerPassInterface
{
    const MATCHER_SERVICE = 'oro_layout.loader.resource_matcher';
    const VOTER_TAG_NAME  = 'layout.resource_matcher.voter';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::MATCHER_SERVICE)) {
            $matcherDef = $container->getDefinition(self::MATCHER_SERVICE);

            foreach ($container->findTaggedServiceIds(self::VOTER_TAG_NAME) as $serviceId => $tag) {
                $priority = isset($tag[0]['priority']) ? $tag[0]['priority'] : 0;

                $matcherDef->addMethodCall('addVoter', [new Reference($serviceId), $priority]);
            }
        }
    }
}
