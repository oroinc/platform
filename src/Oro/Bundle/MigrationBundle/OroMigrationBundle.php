<?php

namespace Oro\Bundle\MigrationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\MigrationExtensionPass;

class OroMigrationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MigrationExtensionPass());
    }
}
