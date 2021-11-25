<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Insert additional dependencies to translator service.
 */
class TranslatorDependencyPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $translatorDef = $container->getDefinition('translator.default');

        $translatorDef->setClass('Oro\Bundle\TranslationBundle\Translation\Translator');
        $translatorDef->addMethodCall(
            'setDatabaseMetadataCache',
            [new Reference('oro_translation.database_translation.metadata.cache')]
        );
        $translatorDef->addMethodCall(
            'setResourceCache',
            [new Reference('oro_translation.resource.cache')]
        );

        $translatorDef->addMethodCall(
            'setStrategyProvider',
            [new Reference('oro_translation.strategy.provider')]
        );

        $translatorDef->addMethodCall(
            'setTranslationDomainProvider',
            [new Reference('oro_translation.provider.translation_domain')]
        );

        $translatorDef->addMethodCall(
            'setEventDispatcher',
            [new Reference('event_dispatcher')]
        );

        $translatorDef->addMethodCall('setLogger', [new Reference('logger')]);

        $translatorDef->addMethodCall(
            'setApplicationState',
            [new Reference('oro_distribution.handler.application_status')]
        );
        $translatorDef->addMethodCall(
            'setMessageCatalogueSanitizer',
            [new Reference('oro_translation.message_catalogue_sanitizer')]
        );
        $translatorDef->setPublic(true);
    }
}
