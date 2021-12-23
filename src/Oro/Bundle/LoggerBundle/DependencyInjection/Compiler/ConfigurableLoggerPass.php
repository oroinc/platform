<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\Monolog\ConfigurableFingersCrossedHandler;
use Oro\Bundle\LoggerBundle\Monolog\DisableFilterHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\DisableHandlerWrapper;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides fingers_crossed monolog handlers with {@see ConfigurableFingersCrossedHandler}
 * and decorates *_mailer handlers with {@see DisableHandlerWrapper}
 */
class ConfigurableLoggerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $configuration = $container->getExtension('monolog')->getConfiguration([], $container);
        $config = (new Processor())->processConfiguration($configuration, $container->getExtensionConfig('monolog'));

        foreach ($config['handlers'] as $handlerName => $handlerConfig) {
            switch ($handlerConfig['type']) {
                case 'fingers_crossed':
                    $handlerId = $this->getHandlerId($handlerName);
                    $container->getDefinition($handlerId)
                        ->setClass(ConfigurableFingersCrossedHandler::class)
                        ->addMethodCall('setLogLevelConfig', [new Reference('oro_logger.log_level_config_provider')]);
                    break;
                case 'filter':
                    $handlerId = $this->getHandlerId($handlerName);
                    $disableWrapperServiceId = $handlerId.'.disable_filter_wrapper';
                    $container
                        ->register($disableWrapperServiceId, DisableFilterHandlerWrapper::class)
                        ->setArguments(
                            [
                                new Reference('oro_logger.log_level_config_provider'),
                                new Reference('.inner'),
                            ]
                        )
                        ->setDecoratedService($handlerId)
                        ->setPublic(false);
                    break;
                case 'swift_mailer':
                case 'native_mailer':
                case 'symfony_mailer':
                    $handlerId = $this->getHandlerId($handlerName);
                    $disableWrapperServiceId = $handlerId.'.disable_wrapper';
                    $container
                        ->register($disableWrapperServiceId, DisableHandlerWrapper::class)
                        ->setArguments(
                            [
                                new Reference('oro_logger.log_level_config_provider'),
                                new Reference('.inner'),
                            ]
                        )
                        ->setDecoratedService($handlerId)
                        ->setPublic(false);
                    break;
            }
        }
    }

    private function getHandlerId(string $name): string
    {
        return sprintf('monolog.handler.%s', $name);
    }
}
