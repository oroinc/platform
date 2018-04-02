<?php

namespace Oro\Bundle\MigrationBundle;

use Oro\Bundle\MigrationBundle\DependencyInjection\Compiler\MigrationExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMigrationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MigrationExtensionPass());
    }
}
