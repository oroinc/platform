<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\Twig\Environment;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Substitutes the class for the Twig environment service.
 */
class ReplaceTwigEnvironmentPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $twigDefinition = $container->getDefinition('twig');
        $options = $twigDefinition->getArgument(1);
        /**
         * This option needed for correct render twig templates and performance optimizations
         * If the use_yield option set to false - template rendering time up to more than 3 times
         * @see \Twig\Template::yield
         */
        $options['use_yield'] = true;
        $container->getDefinition('twig')
            ->setClass(Environment::class)
            ->setArgument('$options', $options);
    }
}
