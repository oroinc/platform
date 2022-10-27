<?php

namespace Oro\Bundle\DataGridBundle;

use Oro\Bundle\DataGridBundle\DependencyInjection\Compiler;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDataGridBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\DataSourcesPass());
        $container->addCompilerPass(new Compiler\ExtensionsPass());
        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_datagrid.extension.formatter',
            'oro_datagrid.extension.formatter.property',
            'type'
        ));
        $container->addCompilerPass(new Compiler\ActionsPass(
            'oro_datagrid.extension.action.factory',
            'oro_datagrid.extension.action.type'
        ));
        $container->addCompilerPass(new Compiler\ActionsPass(
            'oro_datagrid.extension.mass_action.factory',
            'oro_datagrid.extension.mass_action.type'
        ));
        $container->addCompilerPass(new Compiler\SetDatagridEventListenersLazyPass());
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_datagrid.extension.board',
            'oro_datagrid.board_processor',
            'alias'
        ));
    }
}
