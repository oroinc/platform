<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormGuesserCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('form.extension')) {
            return;
        }

        // need to sort guessers according to priority
        $guessers = [];
        foreach ($container->findTaggedServiceIds('form.type_guesser') as $id => $attributes) {
            foreach ($attributes as $eachTag) {
                $priority = !empty($eachTag['priority']) ? $eachTag['priority'] : 0;

                $guessers[$priority][] = new Reference($id);
            }
        }

        // sort by priority and flatten
        krsort($guessers);
        $guessers = array_merge(...$guessers);

        $formExtension = $container->getDefinition('form.extension');
        $formExtension->replaceArgument(2, new IteratorArgument($guessers));
    }
}
