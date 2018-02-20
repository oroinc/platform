<?php

namespace Oro\Bundle\LayoutBundle;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockViewSerializerNormalizersPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\CustomImageFilterProvidersCompilerPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ExpressionCompilerPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ResourcePathProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
        $container->addCompilerPass(new CustomImageFilterProvidersCompilerPass());
        $container->addCompilerPass(new BlockViewSerializerNormalizersPass());
    }
}
