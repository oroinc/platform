<?php

namespace Oro\Bundle\EntityMergeBundle;

use Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler\AddAccessorCompilerPass;
use Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler\AddStepCompilerPass;
use Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler\AddStrategyCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroEntityMergeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddAccessorCompilerPass());
        $container->addCompilerPass(new AddStrategyCompilerPass());
        $container->addCompilerPass(new AddStepCompilerPass());
    }
}
