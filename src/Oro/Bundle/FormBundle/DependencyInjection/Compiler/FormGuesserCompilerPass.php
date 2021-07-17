<?php

namespace Oro\Bundle\FormBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sorts form guessers by priority.
 */
class FormGuesserCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        $guessers = $this->findAndSortTaggedServices('form.type_guesser', $container);
        $container->getDefinition('form.extension')
            ->replaceArgument(2, new IteratorArgument($guessers));
    }
}
