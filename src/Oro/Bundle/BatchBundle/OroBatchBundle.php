<?php

namespace Oro\Bundle\BatchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'AkeneoBatchBundle';
    }
}
