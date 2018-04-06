<?php

namespace Oro\Bundle\TranslationBundle;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationPackagesProviderPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationStrategyPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
        $container->addCompilerPass(new TranslationStrategyPass());
        $container->addCompilerPass(new TranslationPackagesProviderPass());
        $container->addCompilerPass(new DynamicAssetVersionPass('translations'));
    }
}
