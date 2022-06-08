<?php

namespace Oro\Bundle\CronBundle;

use Oro\Bundle\CronBundle\DependencyInjection\Compiler\ConsoleCommandListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCronBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ConsoleCommandListenerPass());
    }
}
