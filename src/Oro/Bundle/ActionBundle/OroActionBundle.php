<?php

namespace Oro\Bundle\ActionBundle;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ActionPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConditionPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorFilterPass;
use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Oro\Component\DependencyInjection\Compiler\ServiceLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroActionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ServiceLocatorCompilerPass(
            'oro_action.condition_locator',
            'oro_action.condition'
        ));
        $container->addCompilerPass(new ConditionPass());
        $container->addCompilerPass(new ServiceLocatorCompilerPass(
            'oro_action.action_locator',
            'oro_action.action'
        ));
        $container->addCompilerPass(new ActionPass());
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_action.datagrid.mass_action_provider.registry',
            'oro_action.datagrid.mass_action_provider',
            'alias'
        ));
        $container->addCompilerPass(new DuplicatorFilterPass());
        $container->addCompilerPass(new DuplicatorMatcherPass());
    }
}
