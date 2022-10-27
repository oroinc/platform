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
    private const TWIG_TEMPLATE_CACHE_WARMER = 'twig.template_cache_warmer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::TWIG_TEMPLATE_CACHE_WARMER)) {
            /**
             * the Symfony does not register the Twig template cache warmer when Twig cache is disabled
             * @see \Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension
             */
            return;
        }

        $container->getDefinition(self::TWIG_TEMPLATE_CACHE_WARMER)
            ->setClass(TwigTemplateCacheWarmer::class);
    }
}
