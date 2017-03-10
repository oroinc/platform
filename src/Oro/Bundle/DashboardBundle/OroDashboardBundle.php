<?php

namespace Oro\Bundle\DashboardBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\DashboardBundle\DependencyInjection\Compiler\BigNumberProviderPass;
use Oro\Bundle\DashboardBundle\DependencyInjection\Compiler\ValueConvertersPass;
use Oro\Bundle\DashboardBundle\DependencyInjection\Compiler\WidgetProviderFilterPass;

class OroDashboardBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ValueConvertersPass());
        $container->addCompilerPass(new BigNumberProviderPass());
        $container->addCompilerPass(new WidgetProviderFilterPass());
    }
}
