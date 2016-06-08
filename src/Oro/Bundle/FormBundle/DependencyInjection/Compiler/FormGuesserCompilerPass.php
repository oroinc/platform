<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

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
        $guessers = array();
        foreach ($container->findTaggedServiceIds('form.type_guesser') as $id => $attributes) {
            foreach ($attributes as $eachTag) {
                $priority = !empty($eachTag['priority']) ? $eachTag['priority'] : 0;
                $guessers[$id] = $priority;
            }
        }

        arsort($guessers, SORT_NUMERIC);

        $formExtension = $container->getDefinition('form.extension');
        $formExtension->replaceArgument(3, array_keys($guessers));
    }
}
