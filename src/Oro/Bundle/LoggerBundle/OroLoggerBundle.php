<?php

namespace Oro\Bundle\LoggerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\SwiftMailerHandlerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\DetailedLogsHandlerPass;

class OroLoggerBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DetailedLogsHandlerPass());
        $container->addCompilerPass(new SwiftMailerHandlerPass());
    }
}
