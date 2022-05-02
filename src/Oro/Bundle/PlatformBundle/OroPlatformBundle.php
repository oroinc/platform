<?php

namespace Oro\Bundle\PlatformBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\EntityListenerPass;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;
use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\ProfilerCompilerPass;
use Oro\Component\DependencyInjection\Compiler\ServiceLinkCompilerPass;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPlatformBundle extends Bundle
{
    public const PACKAGE_NAME = 'oro/platform';

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\LazyServicesCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new Compiler\OptionalListenersCompilerPass());
        $container->addCompilerPass(new ServiceLinkCompilerPass('oro_service_link', ServiceLink::class));
        if ($container instanceof ExtendedContainerBuilder) {
            $container->addCompilerPass(new Compiler\LazyDoctrineOrmListenersPass());
            $container->moveCompilerPassBefore(
                Compiler\LazyDoctrineOrmListenersPass::class,
                EntityListenerPass::class
            );

            $container->addCompilerPass(new Compiler\LazyDoctrineListenersPass());
            $container->moveCompilerPassBefore(
                Compiler\LazyDoctrineListenersPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );

            $container->addCompilerPass(new Compiler\UpdateDoctrineEventHandlersPass());
            $container->moveCompilerPassBefore(
                Compiler\UpdateDoctrineEventHandlersPass::class,
                RegisterEventListenersAndSubscribersPass::class
            );
        }
        $container->addCompilerPass(new Compiler\UndoLazyEntityManagerPass());
        $container->addCompilerPass(new Compiler\JmsSerializerPass());
        $container->addCompilerPass(new Compiler\TwigServiceLocatorPass());
        $container->addCompilerPass(new Compiler\DoctrineTagMethodPass());
        $container->addCompilerPass(
            new Compiler\ProfilerStorageCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            32
        );
        $container->addCompilerPass(new Compiler\MergeServiceLocatorsCompilerPass(
            'form.type_extension',
            'oro_platform.form.type_extension.service_locator'
        ), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new Compiler\MergeServiceLocatorsCompilerPass(
            'doctrine.event_listener',
            'oro_platform.doctrine.event_listener.service_locator'
        ), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new Compiler\MergeServiceLocatorsCompilerPass(
            'doctrine.orm.entity_listener',
            'oro_platform.doctrine.event_listener.service_locator'
        ), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new Compiler\MergeServiceLocatorsCompilerPass(
            'security.voter',
            'oro_platform.security.voter.service_locator'
        ), PassConfig::TYPE_BEFORE_REMOVING);

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new Compiler\MergeServiceLocatorsCompilerPass(
                'oro_platform.tests.merge_service_locators',
                'oro_platform.tests.merge_service_locators.service_locator'
            ), PassConfig::TYPE_BEFORE_REMOVING);
        }

        $container->addCompilerPass(new ProfilerCompilerPass());
    }
}
