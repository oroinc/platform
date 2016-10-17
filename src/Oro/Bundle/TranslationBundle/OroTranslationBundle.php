<?php

namespace Oro\Bundle\TranslationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;

class OroTranslationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TranslatorDependencyPass());
        $container->addCompilerPass(new DebugTranslatorPass());
        $container->addCompilerPass(new TranslationContextResolverPass());
    }
}
