<?php

namespace Oro\Bundle\InstallerBundle;

use Oro\Bundle\InstallerBundle\DependencyInjection\Compiler\ReadOnlyConnectionAwareCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroInstallerBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ReadOnlyConnectionAwareCompilerPass());
    }
}
