<?php

namespace Oro\Bundle\MigrationBundle;

use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\MigrationExtensionPass;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\ServiceContainerRealRefPass;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\ServiceContainerWeakRefPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMigrationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new MigrationExtensionPass());
        $container->addCompilerPass(new ServiceContainerWeakRefPass(), PassConfig::TYPE_BEFORE_REMOVING, -32);
        $container->addCompilerPass(new ServiceContainerRealRefPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
