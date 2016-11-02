<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\LoggerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler;

/**
 * This compiler pass hides detailed logs handler's nested handler for all channels
 * and also injects configured nested handler into detailed logs handler
 */
class DetailedLogsHandlerPass implements CompilerPassInterface
{
    const DETAILED_LOGS_HANDLER_SERVICE_PREFIX = 'oro_logger.monolog.detailed_logs.handler.';
    const DETAILED_LOGS_HANDLER_PROTOTYPE_ID = 'oro_logger.monolog.detailed_logs.handler.prototype';
    const MONOLOG_LOGGER_SERVICE_ID = 'monolog.logger';
    const MONOLOG_HANDLERS_TO_CHANNELS_PARAM = 'monolog.handlers_to_channels';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::MONOLOG_LOGGER_SERVICE_ID) ||
            !$container->has(self::DETAILED_LOGS_HANDLER_PROTOTYPE_ID)
        ) {
            return;
        }

        $this->removeNestedHandlersFromHandlersToChannelsParam($container);
        $this->removeNestedHandlersFromAllChannels($container);
    }

    /**
     * @param ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    protected function removeNestedHandlersFromHandlersToChannelsParam(ContainerBuilder $container)
    {
        $handlersToChannels = $container->getParameter(self::MONOLOG_HANDLERS_TO_CHANNELS_PARAM);
        $handlerIds = array_keys($handlersToChannels);

        for ($i = 0; $i < count($handlerIds); $i++) {
            $handler = $container->findDefinition($handlerIds[$i]);
            if ($handler->getClass() == DetailedLogsHandler::class) {
                if ($i == 0) {
                    throw new InvalidConfigurationException(
                        'Detailed logger is not configured properly. Please specify nested handler'
                    );
                }

                $nestedHandlerId = $handlerIds[$i - 1];
                unset($handlersToChannels[$nestedHandlerId]);
            }
        }

        $container->setParameter(self::MONOLOG_HANDLERS_TO_CHANNELS_PARAM, $handlersToChannels);
    }

    /**
     * @param ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    protected function removeNestedHandlersFromAllChannels(ContainerBuilder $container)
    {
        $loggers = [self::MONOLOG_LOGGER_SERVICE_ID => $container->findDefinition(self::MONOLOG_LOGGER_SERVICE_ID)];

        foreach ($container->findTaggedServiceIds(self::MONOLOG_LOGGER_SERVICE_ID) as $tags) {
            foreach ($tags as $tag) {
                $loggerId = sprintf(self::MONOLOG_LOGGER_SERVICE_ID . '.%s', $tag['channel']);
                $loggers[$loggerId] = $container->findDefinition($loggerId);
            }
        }

        foreach ($loggers as $logger) {
            $calls = array_filter($logger->getMethodCalls(), function ($call) {
                return $call[0] == 'pushHandler';
            });

            foreach ($calls as $i => &$call) {
                $handlerId = (string)$call[1][0];
                $handler = $container->findDefinition($handlerId);

                if ($handler->getClass() != DetailedLogsHandler::class) {
                    continue;
                }

                if ($i == 0) {
                    throw new InvalidConfigurationException(
                        'Detailed logger is not configured properly. Please specify nested handler'
                    );
                }

                $nestedHandlerId = (string)$calls[$i - 1][1][0];
                $handlerName = substr($handlerId, strrpos($handlerId, '.') + 1);
                $newHandlerId = self::DETAILED_LOGS_HANDLER_SERVICE_PREFIX . $handlerName;

                $newHandler = new DefinitionDecorator(self::DETAILED_LOGS_HANDLER_PROTOTYPE_ID);
                $newHandler->addMethodCall('setHandler', [new Reference($nestedHandlerId)]);
                $container->setDefinition($newHandlerId, $newHandler);

                $call[1][0] = new Reference($newHandlerId);

                unset($calls[$i - 1]);
                $logger->removeMethodCall('pushHandler')->setMethodCalls($calls);

                break;
            }
        }
    }
}
