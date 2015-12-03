<?php

namespace Oro\Bundle\CronBundle;

use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobSerializerMetadataPass;
use Oro\Bundle\CronBundle\DependencyInjection\Compiler\JobStatisticParameterPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCronBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new JobStatisticParameterPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new JobSerializerMetadataPass());
    }
}
