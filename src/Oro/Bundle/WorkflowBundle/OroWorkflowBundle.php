<?php

namespace Oro\Bundle\WorkflowBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

class OroWorkflowBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\AddAttributeNormalizerCompilerPass());
        $container->addCompilerPass(new Compiler\AddWorkflowValidationLoaderCompilerPass());
        $container->addCompilerPass(new Compiler\WorkflowChangesEventsCompilerPass());
        $container->addCompilerPass(new Compiler\EventTriggerExtensionCompilerPass());
        $container->addCompilerPass(new Compiler\WorkflowConfigurationHandlerCompilerPass);
        $container->addCompilerPass(new Compiler\WorkflowDefinitionBuilderExtensionCompilerPass);

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass->add(Topics::EXECUTE_PROCESS_JOB);

        $container->addCompilerPass($addTopicMetaPass);
    }
}
