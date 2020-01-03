<?php

namespace Oro\Bundle\DataGridBundle;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedServiceViaAddMethodCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The DataGridBundle bundle class.
 */
class OroDataGridBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CompilerPass\ConfigurationPass());
        $container->addCompilerPass(new CompilerPass\FormattersPass());
        $container->addCompilerPass(new CompilerPass\ActionsPass());
        $container->addCompilerPass(new CompilerPass\GuessPass());
        $container->addCompilerPass(new CompilerPass\InlineEditColumnOptionsGuesserPass());
        $container->addCompilerPass(new CompilerPass\SetDatagridEventListenersLazyPass());
        $container->addCompilerPass(new CompilerPass\BoardProcessorsPass());
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_datagrid.provider.selected_fields',
            'oro_datagrid.selected_fields_provider',
            'addSelectedFieldsProvider'
        ));
    }
}
