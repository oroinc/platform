<?php

namespace Oro\Bundle\WorkflowBundle;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddAttributeNormalizerCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddConditionAndActionCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroWorkflowBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddConditionAndActionCompilerPass());
        $container->addCompilerPass(new AddAttributeNormalizerCompilerPass());
    }
}
