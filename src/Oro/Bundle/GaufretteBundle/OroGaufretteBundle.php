<?php

namespace Oro\Bundle\GaufretteBundle;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler\SetGaufretteFilesystemsLazyPass;
use Oro\Bundle\GaufretteBundle\DependencyInjection\Factory\LocalConfigurationFactory;
use Oro\Bundle\GaufretteBundle\DependencyInjection\OroGaufretteExtension;
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

        /** @var OroGaufretteExtension $extension */
        $extension = $container->getExtension('oro_gaufrette');
        $extension->addConfigurationFactory(new LocalConfigurationFactory());
    }
}
