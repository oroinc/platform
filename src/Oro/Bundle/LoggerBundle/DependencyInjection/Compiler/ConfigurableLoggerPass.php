<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\Monolog\ConfigurableFingersCrossedHandler;
use Oro\Bundle\LoggerBundle\Monolog\DisableDeprecationsHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\DisableFilterHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\DisableHandlerWrapper;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides fingers_crossed monolog handlers with {@see ConfigurableFingersCrossedHandler}
 * and decorates *_mailer handlers with {@see DisableHandlerWrapper}
 */
class ConfigurableLoggerPass implements CompilerPassInterface
{
    public const string DISABLE_FILTER_WRAPPER_POSTFIX = '.disable_filter_wrapper';
    public const string DISABLE_DEPRECATIONS_WRAPPER_POSTFIX = '.disable_deprecations';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $configuration = $container->getExtension('monolog')->getConfiguration([], $container);
        $config = (new Processor())->processConfiguration($configuration, $container->getExtensionConfig('monolog'));
        foreach ($config['handlers'] as $handlerName => $handlerConfig) {
            $handlerId = 'monolog.handler.' . $handlerName;
            switch ($handlerConfig['type']) {
                case 'fingers_crossed':
                    $container->getDefinition($handlerId)
                        ->setClass(ConfigurableFingersCrossedHandler::class)
                        ->addMethodCall('setLogLevelConfig', [new Reference('oro_logger.log_level_config_provider')]);
                    $this->addDisableDeprecationsWrapper($container, $handlerId);
                    break;
                case 'filter':
                    $disableWrapperServiceId = $handlerId . self::DISABLE_FILTER_WRAPPER_POSTFIX;
                    $container
                        ->register($disableWrapperServiceId, DisableFilterHandlerWrapper::class)
                        ->setArguments([
                            new Reference('oro_logger.log_level_config_provider'),
                            new Reference('.inner'),
                        ])
                        ->setDecoratedService($handlerId)
                        ->setPublic(false);
                    $this->addDisableDeprecationsWrapper($container, $handlerId);
                    break;
                case 'service':
                    $this->addDisableDeprecationsWrapper($container, $handlerId);
                    break;
                case 'swift_mailer':
                case 'native_mailer':
                case 'symfony_mailer':
                    $disableWrapperServiceId = $handlerId . '.disable_wrapper';
                    $container
                        ->register($disableWrapperServiceId, DisableHandlerWrapper::class)
                        ->setArguments([
                            new Reference('oro_logger.log_level_config_provider'),
                            new Reference('.inner'),
                        ])
                        ->setDecoratedService($handlerId)
                        ->setPublic(false);
                    break;
            }
        }
    }

    private function addDisableDeprecationsWrapper(ContainerBuilder $container, string $handlerId): void
    {
        if (!$container->hasParameter('oro_platform.collect_deprecations')
            || $container->getParameter('oro_platform.collect_deprecations')
        ) {
            return;
        }

        $disableDeprecationWrapperDefinition = new Definition(
            DisableDeprecationsHandlerWrapper::class,
            [
                new Reference('.inner'),
                '%oro_platform.collect_deprecations%'
            ]
        );
        $disableDeprecationWrapperDefinition->setDecoratedService($handlerId);
        $container->setDefinition(
            $handlerId . self::DISABLE_DEPRECATIONS_WRAPPER_POSTFIX,
            $disableDeprecationWrapperDefinition
        );
    }
}
