<?php

namespace Oro\Bundle\TranslationBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\TranslationBundle\Async\Topics;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The TranslationBundle bundle class.
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
        $container->addCompilerPass(new DynamicAssetVersionPass('translations'));
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_translation.translation_adapters_collection',
            'translation_adapter',
            'alias'
        ));

        $container->addCompilerPass(
            AddTopicMetaPass::create()
                ->add(Topics::JS_TRANSLATIONS_DUMP, 'Dumps JS translations')
        );
    }
}
