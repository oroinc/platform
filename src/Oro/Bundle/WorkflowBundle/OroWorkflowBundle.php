<?php

namespace Oro\Bundle\WorkflowBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;
use Oro\Component\ChainProcessor\DependencyInjection\LoadAndBuildProcessorsCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
        $container->addCompilerPass(new RegisterListenersPass(
            'oro_workflow.changes.event.dispatcher',
            'oro_workflow.changes.listener',
            'oro_workflow.changes.subscriber'
        ));
        $container->addCompilerPass(new Compiler\EventTriggerExtensionCompilerPass());
        $container->addCompilerPass(new Compiler\WorkflowConfigurationHandlerCompilerPass);
        $container->addCompilerPass(new Compiler\WorkflowDefinitionBuilderExtensionCompilerPass);
        $container->addCompilerPass(
            new LoadAndBuildProcessorsCompilerPass(
                'oro_workflow.processor_bag_config_provider',
                'oro_workflow.processor'
            )
        );
        $container->addCompilerPass(new Compiler\EventsCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass->add(Topics::EXECUTE_PROCESS_JOB);

        $container->addCompilerPass($addTopicMetaPass);
    }
}
