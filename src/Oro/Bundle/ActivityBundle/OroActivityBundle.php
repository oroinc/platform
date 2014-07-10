<?php

namespace Oro\Bundle\ActivityBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ActivityBundle\DependencyInjection\Compiler\ActivityWidgetProviderPass;

class OroActivityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ActivityWidgetProviderPass());
    }
}
