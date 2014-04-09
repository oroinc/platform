<?php

namespace Oro\Bundle\InstallerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\InstallerBundle\DependencyInjection\Compiler\InstallerPass;

class OroInstallerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InstallerPass());
    }
}
