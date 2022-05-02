<?php

namespace Oro\Bundle\WorkflowBundle;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;
use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
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
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\DebugWorkflowItemSerializerPass());
        $container->addCompilerPass(new Compiler\AddWorkflowValidationLoaderCompilerPass());
        $container->addCompilerPass(new RegisterListenersPass(
            'oro_workflow.changes.event.dispatcher',
            'oro_workflow.changes.listener',
            'oro_workflow.changes.subscriber'
        ));
        $container->addCompilerPass(new LoadAndBuildProcessorsCompilerPass(
            'oro_workflow.processor_bag_config_provider',
            'oro_workflow.processor'
        ));
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass(
                'oro_workflow.simple_processor_registry',
                'oro_workflow.processor',
                'oro_workflow.simple_processor_registry.inner'
            ),
            PassConfig::TYPE_BEFORE_REMOVING
        );
    }
}
