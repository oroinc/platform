<?php

namespace Oro\Bundle\TranslationBundle;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationCacheWarmerPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The TranslationBundle bundle class.
 */
class OroTranslationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TranslationCacheWarmerPass());
        $container->addCompilerPass(new TranslatorDependencyPass());
        $container->addCompilerPass(new DebugTranslatorPass());
        $container->addCompilerPass(new DynamicAssetVersionPass('translations'));
    }
}
