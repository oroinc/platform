<?php

namespace Oro\Bundle\WorkflowBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddAttributeNormalizerCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddConditionAndActionCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddWorkflowValidationLoaderCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowChangesEventsCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

        $addTopicMetaPass = AddTopicMetaPass::create();
        $addTopicMetaPass
             ->add(Topics::EXECUTE_PROCESS_JOB)
        ;

        $container->addCompilerPass($addTopicMetaPass);
    }
}
