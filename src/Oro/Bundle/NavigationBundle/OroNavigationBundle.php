<?php

namespace Oro\Bundle\NavigationBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\JsRoutingPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroNavigationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new JsRoutingPass());
        $container->addCompilerPass(new MenuBuilderPass());
        $container->addCompilerPass(new DynamicAssetVersionPass('routing'));
        $container->addCompilerPass(new MenuExtensionPass());
        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
            'Oro\Bundle\NavigationBundle\Entity\MenuUpdate' => [
                'title' => 'titles',
                'description' => 'descriptions'
            ]
        ]));
    }
}
