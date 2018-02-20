<?php

namespace Oro\Bundle\InstallerBundle;

use Oro\Bundle\InstallerBundle\DependencyInjection\CompilerPass\NamespaceMigrationProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroInstallerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new NamespaceMigrationProviderPass());
    }
}
