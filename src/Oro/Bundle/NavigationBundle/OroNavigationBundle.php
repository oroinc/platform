<?php

namespace Oro\Bundle\NavigationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\TagGeneratorPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderChainPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\ChainBreadcrumbManagerPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;

class OroNavigationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MenuBuilderChainPass());
        $container->addCompilerPass(new TagGeneratorPass());
        $container->addCompilerPass(new ChainBreadcrumbManagerPass());
        $container->addCompilerPass(new DynamicAssetVersionPass('routing'));
    }
}
