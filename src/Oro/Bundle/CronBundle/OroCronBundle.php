<?php

namespace Oro\Bundle\CronBundle;

use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCronBundle extends Bundle
{

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $addTopicPass = AddTopicMetaPass::create()
            ->add(Topics::RUN_COMMAND, 'Creates a job to run console command')
            ->add(Topics::RUN_COMMAND_DELAYED, 'Runs job with symfony console command')
        ;

        $container->addCompilerPass($addTopicPass);
    }
}
