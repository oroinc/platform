<?php

namespace Oro\Bundle\BatchBundle;

use Oro\Bundle\BatchBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Batch Bundle
 *
 */
class OroBatchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new Compiler\RegisterNotifiersPass())
            ->addCompilerPass(new Compiler\PushBatchLogHandlerPass())
            ->addCompilerPass(new Compiler\RegisterJobsPass());
    }
}
