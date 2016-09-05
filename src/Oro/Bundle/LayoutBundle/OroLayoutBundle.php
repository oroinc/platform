<?php

namespace Oro\Bundle\LayoutBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ResourcePathProvidersPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ExpressionCompilerPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class OroLayoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new ExpressionCompilerPass());
        $container->addCompilerPass(new ResourcePathProvidersPass());
        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
