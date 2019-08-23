<?php

namespace Oro\Bundle\TranslationBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\TranslationBundle\Async\Topics;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationAdaptersPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationContextResolverPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationPackagesProviderPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationStrategyPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers compiler passes extensions
 */
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
        $container->addCompilerPass(new TranslationAdaptersPass());

        $addTopicPass = AddTopicMetaPass::create()->add(Topics::JS_TRANSLATIONS_DUMP, 'Dumps JS translations');
        $container->addCompilerPass($addTopicPass);
    }
}
