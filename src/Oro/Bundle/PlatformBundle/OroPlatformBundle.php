<?php

namespace Oro\Bundle\PlatformBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\EntityListenerPass;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\DependencyInjection\Compiler\ServiceLinkCompilerPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyDoctrineListenersPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyDoctrineOrmListenersPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyServicesCompilerPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\OptionalListenersCompilerPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineConfigurationPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UndoLazyEntityManagerPass;

class OroPlatformBundle extends Bundle
{
    const PACKAGE_NAME = 'oro/platform';
    const PACKAGE_DIST_NAME = 'oro/platform-dist';

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new LazyServicesCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new OptionalListenersCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        // @todo: Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink is used to avoid BC break
        $container->addCompilerPass(
            new ServiceLinkCompilerPass(
                'oro_service_link',
                'Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink'
            )
        );
        $container->addCompilerPass(new UpdateDoctrineConfigurationPass());
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new LazyDoctrineOrmListenersPass());
            $container->moveCompilerPassBefore(
                LazyDoctrineOrmListenersPass::class,
                EntityListenerPass::class
            );

            $container->addCompilerPass(new LazyDoctrineListenersPass());
            $container->moveCompilerPassBefore(
                LazyDoctrineListenersPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );

            $container->addCompilerPass(new UpdateDoctrineEventHandlersPass());
            $container->moveCompilerPassBefore(
                UpdateDoctrineEventHandlersPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );
        }
        $container->addCompilerPass(new UndoLazyEntityManagerPass());
    }
}
