<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TranslatorDependencyPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('translator.default')->addMethodCall(
            'setDatabaseMetadataCache',
            [$container->getDefinition('oro_translation.database_translation.metadata.cache')]
        );
    }
}
