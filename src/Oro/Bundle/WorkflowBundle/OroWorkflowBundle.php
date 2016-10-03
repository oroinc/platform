<?php

namespace Oro\Bundle\WorkflowBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddAttributeNormalizerCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddWorkflowValidationLoaderCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\EventTriggerExtensionCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowChangesEventsCompilerPass;

class OroWorkflowBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddAttributeNormalizerCompilerPass());
        $container->addCompilerPass(new AddWorkflowValidationLoaderCompilerPass());
        $container->addCompilerPass(new WorkflowChangesEventsCompilerPass());
        $container->addCompilerPass(new EventTriggerExtensionCompilerPass());
    }
}
