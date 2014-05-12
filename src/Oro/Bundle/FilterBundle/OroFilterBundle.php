<?php

namespace Oro\Bundle\FilterBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\FilterBundle\DependencyInjection\CompilerPass\FilterTypesPass;

class OroFilterBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FilterTypesPass());
    }
}
