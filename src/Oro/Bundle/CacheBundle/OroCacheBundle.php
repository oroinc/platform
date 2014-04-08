<?php

namespace Oro\Bundle\CacheBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;

class OroCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CacheConfigurationPass());
    }
}
