<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\Translation\DebugTranslator;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Configure the debug translator when it is enabled.
 */
class DebugTranslatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('oro_translation.debug_translator')) {
            $translatorDef = $container->getDefinition('translator.default');
            if ($translatorDef->getClass() !== Translator::class) {
                throw new InvalidArgumentException(sprintf(
                    'The class for the "translator.default" service must be "%s", given "%s".',
                    Translator::class,
                    $translatorDef->getClass()
                ));
            }
            $translatorDef->setClass(DebugTranslator::class);
        }
    }
}
