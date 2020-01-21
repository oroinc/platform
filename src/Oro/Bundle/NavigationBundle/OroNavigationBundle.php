<?php

namespace Oro\Bundle\NavigationBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuExtensionPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\DynamicAssetVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The NavigationBundle bundle class.
 */
class OroNavigationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MenuBuilderPass());
        $container->addCompilerPass(new DynamicAssetVersionPass('routing'));
        $container->addCompilerPass(new MenuExtensionPass());
        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\NavigationBundle\Entity\MenuUpdate' => [
                'title' => 'titles',
                'description' => 'descriptions'
            ]
        ]));
    }
}
