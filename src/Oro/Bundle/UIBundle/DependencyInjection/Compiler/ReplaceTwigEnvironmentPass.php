<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\Cache\TemplateCacheCacheWarmer;
use Oro\Bundle\UIBundle\Twig\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Substitutes the class for the Twig environment service.
 */
class ReplaceTwigEnvironmentPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('twig')
            ->setClass(Environment::class);

        if ($container->hasDefinition('twig.cache_warmer')) {
            $container->getDefinition('twig.cache_warmer')
                ->setClass(TemplateCacheCacheWarmer::class);
        }
    }
}
