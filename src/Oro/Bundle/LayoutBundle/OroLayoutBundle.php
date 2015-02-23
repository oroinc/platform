<?php

namespace Oro\Bundle\LayoutBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass;
use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigExpressionCompilerPass;

class OroLayoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new ConfigExpressionCompilerPass());
    }
}
