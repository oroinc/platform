<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\Cache\TwigTemplateCacheWarmer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Substitutes the Twig template warmer to skip non Twig files in "layouts" directories.
 */
class TwigTemplateCacheWarmerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('twig.template_cache_warmer')
            ->setClass(TwigTemplateCacheWarmer::class);
    }
}
