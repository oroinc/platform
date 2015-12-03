<?php

namespace Oro\Bundle\EntityBundle;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DoctrineSqlFiltersConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroEntityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DoctrineSqlFiltersConfigurationPass());
    }
}
