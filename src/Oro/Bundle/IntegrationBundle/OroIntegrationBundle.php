<?php

namespace Oro\Bundle\IntegrationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\IntegrationBundle\DependencyInjection\CompilerPass\TypesPass;

class OroIntegrationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TypesPass());
    }
}
