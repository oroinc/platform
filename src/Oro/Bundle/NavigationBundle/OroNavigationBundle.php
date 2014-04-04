<?php

namespace Oro\Bundle\NavigationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\TagGeneratorPass;
use Oro\Bundle\NavigationBundle\DependencyInjection\Compiler\MenuBuilderChainPass;

class OroNavigationBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/navigation.yml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MenuBuilderChainPass());
        $container->addCompilerPass(new TagGeneratorPass());
    }
}
