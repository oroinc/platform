<?php

namespace Oro\Bundle\CronBundle;

use Symfony\Component\ClassLoader\MapClassLoader;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobStatisticParameterPass;
use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobSerializerMetadataPass;

class OroCronBundle extends Bundle
{
    public function __construct()
    {
        // Change path to JMSJobQueueBundle class file to set custom JobQueueBundle loader
        $loader = new MapClassLoader(
            [
                'JMS\JobQueueBundle\JMSJobQueueBundle' =>  __DIR__ . '/JobQueueBundle/JMSJobQueueBundle.php'
            ]
        );
        $loader->register(true);
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
