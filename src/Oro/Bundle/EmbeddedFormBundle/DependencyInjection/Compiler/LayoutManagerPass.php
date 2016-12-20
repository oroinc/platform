<?php

namespace Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LayoutManagerPass implements CompilerPassInterface
{
    const LAYOUT_FACTORY_BUILDER_SERVICE_ID = 'oro_layout.layout_factory_builder';
    const LAYOUT_MANGER_SERVICE_ID = 'oro_layout.layout_manager';
    const EF_LAYOUT_FACTORY_BUILDER_SERVICE_ID = 'oro_embedded_form.layout_factory_builder';
    const EF_LAYOUT_MANGER_SERVICE_ID = 'oro_embedded_form.layout_manager';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $layoutFactoryBuilderDef = $container->findDefinition(self::LAYOUT_FACTORY_BUILDER_SERVICE_ID);
        $layoutManagerDef = $container->findDefinition(self::LAYOUT_MANGER_SERVICE_ID);

        $embeddedFormLayoutFactoryBuilderDef = new Definition();
        $embeddedFormLayoutFactoryBuilderDef
            ->setClass($layoutFactoryBuilderDef->getClass())
            ->setArguments($layoutFactoryBuilderDef->getArguments())
            ->replaceArgument(1, null)
            ->setMethodCalls($layoutFactoryBuilderDef->getMethodCalls())
        ;

        $embeddedFormLayoutManagerDef = new Definition();
        $embeddedFormLayoutManagerDef
            ->setClass($layoutManagerDef->getClass())
            ->setArguments($layoutManagerDef->getArguments())
            ->replaceArgument(0, new Reference(self::EF_LAYOUT_FACTORY_BUILDER_SERVICE_ID))
        ;

        $container->setDefinition(self::EF_LAYOUT_FACTORY_BUILDER_SERVICE_ID, $embeddedFormLayoutFactoryBuilderDef);
        $container->setDefinition(self::EF_LAYOUT_MANGER_SERVICE_ID, $embeddedFormLayoutManagerDef);
    }
}
