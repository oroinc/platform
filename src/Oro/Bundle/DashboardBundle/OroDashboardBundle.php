<?php

namespace Oro\Bundle\DashboardBundle;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDashboardBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_dashboard.widget_config_value.provider',
            'oro_dashboard.value.converter',
            'form_type'
        ));
        $container->addCompilerPass(new PriorityTaggedLocatorCompilerPass(
            'oro_dashboard.provider.big_number.processor',
            'oro_dashboard.big_number.provider',
            'alias'
        ));
    }
}
