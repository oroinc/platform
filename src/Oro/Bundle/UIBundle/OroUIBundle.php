<?php

namespace Oro\Bundle\UIBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ContentProviderPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ViewActionWidgetProviderPass;

class OroUIBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ViewActionWidgetProviderPass());
        $container->addCompilerPass(new ContentProviderPass());
    }
}
