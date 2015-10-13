<?php

namespace Oro\Bundle\DataGridBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ActionsPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\MassActionsPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\FormattersPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\GuessPass;
use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\InlineHandlerPass;

class OroDataGridBundle extends Bundle
{
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
        $container->addCompilerPass(new GuessPass());
        $container->addCompilerPass(new InlineHandlerPass());
    }
}
