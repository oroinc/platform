<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Insert additional dependencies to translator service.
 */
class TranslatorDependencyPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('translator.default')
            ->setClass(Translator::class)
            ->setPublic(true)
            ->addMethodCall(
                'setStrategyProvider',
                [new Reference('oro_translation.strategy.provider')]
            )
            ->addMethodCall(
                'setResourceCache',
                [new Reference('oro_translation.resource.cache')]
            )
            ->addMethodCall(
                'setLogger',
                [new Reference('logger')]
            )
            ->addMethodCall(
                'setMessageCatalogueSanitizer',
                [new Reference('oro_translation.message_catalogue_sanitizer')]
            )
            ->addMethodCall(
                'setSanitizationErrorCollection',
                [new Reference('oro_translation.translation_message_sanitization_errors')]
            )
            ->addMethodCall(
                'setDynamicTranslationProvider',
                [new Reference('oro_translation.dynamic_translation_provider')]
            );
    }
}
