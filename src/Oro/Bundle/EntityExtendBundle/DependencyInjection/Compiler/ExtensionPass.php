<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExtensionPass implements CompilerPassInterface
{
    const GENERATOR_NAME = 'oro_entity_extend.entity_generator';
    const GENERATOR_TAG  = 'oro_entity_extend.entity_generator_extension';

    const DUMPER_NAME = 'oro_entity_extend.tools.dumper';
    const DUMPER_TAG  = 'oro_entity_extend.entity_config_dumper_extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $linkedServices = [];
        if ($container->hasDefinition(self::DUMPER_NAME)) {
            $linkedServices[self::DUMPER_NAME] = self::DUMPER_TAG;
        }
        if ($container->hasDefinition(self::GENERATOR_NAME)) {
            $linkedServices[self::GENERATOR_NAME] = self::GENERATOR_TAG;
        }

        foreach ($linkedServices as $serviceName => $extensionName) {
            $serviceDefinition = $container->getDefinition($serviceName);
            $taggedServices = $container->findTaggedServiceIds($extensionName);

            foreach ($taggedServices as $id => $tagAttributes) {
                if ($container->hasDefinition($id)) {
                    $container->getDefinition($id)->setPublic(false);
                }
                $params = [new Reference($id)];
                if (!empty($tagAttributes[0]['priority'])) {
                    $params[] = (int) $tagAttributes[0]['priority'];
                }

                $serviceDefinition->addMethodCall('addExtension', $params);
            }
        }
    }
}
