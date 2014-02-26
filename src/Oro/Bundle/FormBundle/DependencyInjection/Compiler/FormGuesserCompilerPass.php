<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class FormGuesserCompilerPass implements CompilerPassInterface
{
    const GUESSER_TAG   = 'oro_form.guesser';
    const CHAIN_GUESSER = 'oro_form.guesser.chain';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $chainGuesser = $container->getDefinition(self::CHAIN_GUESSER);

        $guessers = array();
        foreach ($container->findTaggedServiceIds(self::GUESSER_TAG) as $id => $attributes) {
            foreach ($attributes as $eachTag) {
                $priority = !empty($eachTag['priority']) ? $eachTag['priority'] : 0;
                $guessers[$id] = $priority;
            }
        }

        arsort($guessers, SORT_NUMERIC);

        foreach (array_keys($guessers) as $id) {
            $chainGuesser->addMethodCall('addGuesser', array(new Reference($id)));
        }
    }
}
