<?php

namespace Oro\Bundle\BatchBundle;

use Oro\Bundle\BatchBundle\DependencyInjection\Compiler\PushBatchLogHandlerPass;
use Oro\Bundle\BatchBundle\DependencyInjection\Compiler\RegisterJobsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroBatchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PushBatchLogHandlerPass());
        $container->addCompilerPass(new RegisterJobsPass());
    }
}
