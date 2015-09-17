<?php

namespace Oro\Bundle\PlatformBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Component\DependencyInjection\Compiler\ServiceLinkCompilerPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyServicesCompilerPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\OptionalListenersCompilerPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass;

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
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new UpdateDoctrineEventHandlersPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass',
                'Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass'
            );
        }
    }
}
