<?php

namespace Oro\Bundle\BatchBundle;

use Oro\Bundle\BatchBundle\DependencyInjection\Compiler\PushBatchLogHandlerPass;
use Oro\Bundle\BatchBundle\DependencyInjection\Compiler\RegisterJobsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The BatchBundle bundle class.
 */
class OroBatchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new PushBatchLogHandlerPass())
            ->addCompilerPass(new RegisterJobsPass());
    }
}
