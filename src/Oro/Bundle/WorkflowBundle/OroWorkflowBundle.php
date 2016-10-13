<?php

namespace Oro\Bundle\WorkflowBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddAttributeNormalizerCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddConditionAndActionCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddWorkflowValidationLoaderCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\EventTriggerExtensionCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowChangesEventsCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowConfigurationHandlerCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowDefinitionBuilderExtensionCompilerPass;

class OroWorkflowBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddConditionAndActionCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new AddAttributeNormalizerCompilerPass());
        $container->addCompilerPass(new AddWorkflowValidationLoaderCompilerPass());
        $container->addCompilerPass(new WorkflowChangesEventsCompilerPass());
        $container->addCompilerPass(new EventTriggerExtensionCompilerPass());
        $container->addCompilerPass(new WorkflowConfigurationHandlerCompilerPass);
        $container->addCompilerPass(new WorkflowDefinitionBuilderExtensionCompilerPass);
    }
}
