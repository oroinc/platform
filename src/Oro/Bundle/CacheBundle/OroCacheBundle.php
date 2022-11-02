<?php

namespace Oro\Bundle\CacheBundle;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CachePoolConfigurationPass;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\ValidateCacheConfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCacheBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CachePoolConfigurationPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 255);
        $container->addCompilerPass(new CacheConfigurationPass());
        $container->addCompilerPass(new ValidateCacheConfigurationPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
