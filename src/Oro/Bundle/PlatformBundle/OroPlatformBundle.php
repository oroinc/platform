<?php

namespace Oro\Bundle\PlatformBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder;
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
        if ($container instanceof OroContainerBuilder) {
            $container->addCompilerPass(new UpdateDoctrineEventHandlersPass());
            $container->moveCompilerPassBefore(
                'Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\UpdateDoctrineEventHandlersPass',
                'Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass'
            );
        }
    }
}
