<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DebugTranslatorPass implements CompilerPassInterface
{
    const DEBUG_TRANSLATOR_PARAMETER = 'oro_translation.debug_translator';
    const DEBUG_TRANSLATOR_CLASS = 'Oro\Bundle\TranslationBundle\Translation\DebugTranslator';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter(self::DEBUG_TRANSLATOR_PARAMETER)) {
            $container->setParameter('translator.class', self::DEBUG_TRANSLATOR_CLASS);
        }
    }
}
