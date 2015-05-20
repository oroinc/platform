<?php

namespace Oro\Bundle\ActivityListBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\ActivityListProvidersPass;
use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\AfterWidgetProviderPass;
use Oro\Bundle\ActivityListBundle\DependencyInjection\Compiler\BeforeWidgetProviderPass;

class OroActivityListBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new ActivityListProvidersPass())
            ->addCompilerPass(new BeforeWidgetProviderPass())
            ->addCompilerPass(new AfterWidgetProviderPass());
    }
}
