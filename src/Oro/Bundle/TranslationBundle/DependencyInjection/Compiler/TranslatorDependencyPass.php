<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslatorDependencyPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $translatorDef = $container->getDefinition('translator.default');
        $translatorDef->addMethodCall(
            'setDatabaseMetadataCache',
            [new Reference('oro_translation.database_translation.metadata.cache')]
        );
        $translatorDef->addMethodCall(
            'setResourceCache',
            [new Reference('oro_translation.resource.cache')]
        );
    }
}
