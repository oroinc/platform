<?php

namespace Oro\Bundle\UIBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\TwigConfigurationPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\PlaceholderFilterCompilerPass;

class OroUIBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TwigConfigurationPass());
        $container->addCompilerPass(new PlaceholderFilterCompilerPass());
    }
}
