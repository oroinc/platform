<?php

namespace Oro\Bundle\ConfigBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;

class OroConfigBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SystemConfigurationPass());
    }
}
