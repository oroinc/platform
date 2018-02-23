<?php

namespace Oro\Bundle\NavigationBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\ChainBreadcrumbManagerPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderChainPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\TitleReaderPass;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroNavigationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MenuBuilderChainPass());
        $container->addCompilerPass(new ChainBreadcrumbManagerPass());
        $container->addCompilerPass(new DynamicAssetVersionPass('routing'));
        $container->addCompilerPass(new MenuExtensionPass());
        $container->addCompilerPass(new TitleReaderPass());

        $container->addCompilerPass(
            new DefaultFallbackExtensionPass([
                MenuUpdate::class => [
                    'title' => 'titles',
                    'description' => 'descriptions',
                ]
            ])
        );
    }
}
