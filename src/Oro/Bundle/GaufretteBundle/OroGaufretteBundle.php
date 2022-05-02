<?php

namespace Oro\Bundle\GaufretteBundle;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler\ConfigureGaufretteFileManagersPass;
use Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler\ConfigureLocalAdapterPass;
use Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler\SetGaufretteFilesystemsLazyPass;
use Oro\Bundle\GaufretteBundle\DependencyInjection\Factory\LocalConfigurationFactory;
use Oro\Bundle\GaufretteBundle\DependencyInjection\OroGaufretteExtension;
use Oro\Bundle\GaufretteBundle\Stream\Wrapper\ReadonlyStreamWrapper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroGaufretteBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        parent::boot();

        $this->registerReadonlyStreamWrapper();
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigureGaufretteFileManagersPass());
        $container->addCompilerPass(new SetGaufretteFilesystemsLazyPass());
        $container->addCompilerPass(new ConfigureLocalAdapterPass());

        /** @var OroGaufretteExtension $extension */
        $extension = $container->getExtension('oro_gaufrette');
        $extension->addConfigurationFactory(new LocalConfigurationFactory());
    }

    private function registerReadonlyStreamWrapper(): void
    {
        if (!$this->container->hasParameter('oro_gaufrette.stream_wrapper.readonly_protocol')) {
            return;
        }

        /**
         * @see \Knp\Bundle\GaufretteBundle\KnpGaufretteBundle::boot
         */
        ReadonlyStreamWrapper::register(
            $this->container->getParameter('oro_gaufrette.stream_wrapper.readonly_protocol')
        );
        $wrapperFsMap = ReadonlyStreamWrapper::getFilesystemMap();
        $streamWrapperFileSystems = $this->container->getParameter('knp_gaufrette.stream_wrapper.filesystems');
        $fileSystems = $this->container->get('knp_gaufrette.filesystem_map');
        if (empty($streamWrapperFileSystems)) {
            foreach ($fileSystems as $domain => $fileSystem) {
                $wrapperFsMap->set($domain, $fileSystem);
            }
        } else {
            foreach ($streamWrapperFileSystems as $domain => $fileSystem) {
                $wrapperFsMap->set($domain, $fileSystems->get($fileSystem));
            }
        }
    }
}
