<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Changes the priority of the translation cache warmer.
 * This is required because other cache warmers can call the translator
 * and this leads to duplicate building of the translation catalogue.
 */
class TranslationCacheWarmerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('translation.warmer')
            ->clearTag('kernel.cache_warmer')
            ->addTag('kernel.cache_warmer', ['priority' => 100]);
    }
}
