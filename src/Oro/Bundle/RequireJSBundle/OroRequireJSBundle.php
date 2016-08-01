<?php

namespace Oro\Bundle\RequireJSBundle;

use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\CacheProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroRequireJSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CacheProviderPass());
    }
}
