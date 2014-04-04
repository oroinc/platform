<?php

namespace Oro\Bundle\DataGridBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ActionsPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\MassActionsPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\FormattersPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ConfigurationPass;

class OroDataGridBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/datagrid.yml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new FormattersPass());
        $container->addCompilerPass(new ActionsPass());
        $container->addCompilerPass(new MassActionsPass());
    }
}
