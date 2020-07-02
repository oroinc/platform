<?php

namespace Oro\Bundle\GaufretteBundle;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler\SetGaufretteFilesystemsLazyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The GaufretteBundle bundle class.
 */
class OroGaufretteBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SetGaufretteFilesystemsLazyPass());
    }
}
