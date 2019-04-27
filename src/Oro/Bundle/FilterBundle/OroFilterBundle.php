<?php

namespace Oro\Bundle\FilterBundle;

use Oro\Bundle\FilterBundle\DependencyInjection\Compiler\FilterTypesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The FilterBundle bundle class.
 */
class OroFilterBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FilterTypesPass(
            'oro_filter.extension.orm_filter_bag',
            'oro_filter.extension.orm_filter.filter'
        ));
    }
}
