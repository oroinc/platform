<?php

namespace Oro\Bundle\LoggerBundle;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\ConfigurableLoggerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\DoctrineSQLLoggerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\ErrorLogNotificationMailerHandlerPass;
use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\LoggerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroLoggerBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DoctrineSQLLoggerPass());
        $container->addCompilerPass(new ErrorLogNotificationMailerHandlerPass());
        $container->addCompilerPass(new ConfigurableLoggerPass());
        $container->addCompilerPass(new LoggerPass());
    }
}
