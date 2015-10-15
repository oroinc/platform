<?php

namespace Oro\Bundle\CronBundle\JobQueueBundle;

use Doctrine\DBAL\Types\Type;

use JMS\JobQueueBundle\DependencyInjection\CompilerPass\LinkGeneratorsPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JMSJobQueueBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LinkGeneratorsPass());
    }

    public function boot()
    {
        if (!Type::hasType('jms_job_safe_object')) {
            Type::addType('jms_job_safe_object', 'Oro\Bundle\CronBundle\Entity\Type\SafeObjectType');
        }
    }
}
