<?php

namespace Oro\Bundle\CronBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobStatisticParameterPass;
use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobSerializerMetadataPass;

class OroCronBundle extends Bundle
{
    public function __construct()
    {
        // Change alias for JMSJobQueueBundle class to set custom JobQueueBundle loader
        // We should create custom JobQueueBundle loader to avoid unnecessary get
        // all doctrine connections on each request
        if (!class_exists('JMS\JobQueueBundle\JMSJobQueueBundle', false)) {
            class_alias(
                'Oro\Bundle\CronBundle\JobQueue\JMSJobQueueBundle',
                'JMS\JobQueueBundle\JMSJobQueueBundle'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new JobStatisticParameterPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new JobSerializerMetadataPass());
    }
}
