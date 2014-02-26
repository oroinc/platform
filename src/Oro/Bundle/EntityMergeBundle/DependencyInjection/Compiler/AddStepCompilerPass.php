<?php

namespace Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddStepCompilerPass implements CompilerPassInterface
{
    const STEP_TAG = 'oro_entity_merge.step';
    const MERGER_SERVICE = 'oro_entity_merge.merger';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $mergerDefinition = $container->getDefinition(self::MERGER_SERVICE);
        $stepArguments = array();
        foreach ($container->findTaggedServiceIds(self::STEP_TAG) as $id => $attributes) {
            $stepArguments[] = new Reference($id);
        }
        $mergerDefinition->replaceArgument(0, $stepArguments);
    }
}
