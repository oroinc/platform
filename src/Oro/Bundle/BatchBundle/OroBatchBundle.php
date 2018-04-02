<?php

namespace Oro\Bundle\BatchBundle;

use Oro\Bundle\BatchBundle\DependencyInjection\Compiler\DebugBatchPass;
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
    public function getParent()
    {
        return 'AkeneoBatchBundle';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DebugBatchPass());
    }
}
