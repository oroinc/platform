<?php

namespace Oro\Bundle\RequireJSBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\ConfigProviderCompilerPass;

class OroRequireJSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigProviderCompilerPass());
    }
}
