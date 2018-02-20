<?php

namespace Oro\Bundle\CacheBundle;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheWarmerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CacheConfigurationPass());
        $container->addCompilerPass(new CacheWarmerPass());
    }
}
