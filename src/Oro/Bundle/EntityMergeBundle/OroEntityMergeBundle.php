<?php

namespace Oro\Bundle\EntityMergeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler\AddAccessorCompilerPass;
use Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler\AddStrategyCompilerPass;
use Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler\AddStepCompilerPass;

class OroEntityMergeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddAccessorCompilerPass());
        $container->addCompilerPass(new AddStrategyCompilerPass());
        $container->addCompilerPass(new AddStepCompilerPass());
    }
}
