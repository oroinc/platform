<?php

namespace Oro\Bundle\LoggerBundle;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\ConfigurableLoggerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\DetailedLogsHandlerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\LoggerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\SwiftMailerHandlerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The LoggerBundle bundle class.
 */
class OroLoggerBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurableLoggerPass());
        $container->addCompilerPass(new DetailedLogsHandlerPass());
        $container->addCompilerPass(new SwiftMailerHandlerPass());
        $container->addCompilerPass(new LoggerPass());
    }
}
