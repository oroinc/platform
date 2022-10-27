<?php

namespace Oro\Bundle\ActivityBundle;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\WidgetProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroActivityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new WidgetProviderPass(
            'oro_activity.widget_provider.activities',
            'oro_activity.activity_widget_provider'
        ));
    }
}
