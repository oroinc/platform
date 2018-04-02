<?php

namespace Oro\Bundle\ActivityBundle;

use Oro\Bundle\ActivityBundle\DependencyInjection\Compiler\ActivityEntityDeleteHandlerCompilerPass;
use Oro\Bundle\ActivityBundle\DependencyInjection\Compiler\ActivityWidgetProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroActivityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ActivityWidgetProviderPass());
        $container->addCompilerPass(new ActivityEntityDeleteHandlerCompilerPass());
    }
}
