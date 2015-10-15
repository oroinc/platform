<?php

namespace Oro\Bundle\CronBundle;

use Doctrine\DBAL\Types\Type;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobStatisticParameterPass;
use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobSerializerMetadataPass;

class OroCronBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new JobStatisticParameterPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new JobSerializerMetadataPass());
    }

    public function boot()
    {
        if (! Type::hasType('jms_job_safe_object')) {
            Type::addType('jms_job_safe_object', 'Oro\Bundle\CronBundle\Entity\Type\SafeObjectType');
        }
    }

    public function getParent()
    {
        return 'JMSJobQueueBundle';
    }
}
