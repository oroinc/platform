<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\LoggerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler;

/**
 * This compiler pass hides detailed logs handler's nested handler for all channels
 * and also injects configured nested handler into detailed logs handler
 */
class DetailedLogsHandlerPass implements CompilerPassInterface
{
    const DETAILED_LOGS_HANDLER_SERVICE_ID = 'oro_logger.monolog.detailed_logs.handler';
    const MONOLOG_LOGGER_SERVICE_ID = 'monolog.logger';
    const MONOLOG_HANDLERS_TO_CHANNELS_PARAM = 'monolog.handlers_to_channels';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::MONOLOG_LOGGER_SERVICE_ID) ||
            !$container->has(self::DETAILED_LOGS_HANDLER_SERVICE_ID)
        ) {
            return;
        }

        $this->removeNestedHandlerFromHandersToChannelsParam($container);

        $nestedHandlerId = $this->removeNestedHandlerFromAllChannels($container);
        if ($nestedHandlerId !== null) {
            $this->injectNestedHandler($container, $nestedHandlerId);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    protected function removeNestedHandlerFromHandersToChannelsParam(ContainerBuilder $container)
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

                break;
            }
        }

        $container->setParameter(self::MONOLOG_HANDLERS_TO_CHANNELS_PARAM, $handlersToChannels);
    }

    /**
     * @param ContainerBuilder $container
     * @return null|string
     * @throws InvalidConfigurationException
     */
    protected function removeNestedHandlerFromAllChannels(ContainerBuilder $container)
    {
        $loggers = [self::MONOLOG_LOGGER_SERVICE_ID => $container->findDefinition(self::MONOLOG_LOGGER_SERVICE_ID)];

        foreach ($container->findTaggedServiceIds(self::MONOLOG_LOGGER_SERVICE_ID) as $tags) {
            foreach ($tags as $tag) {
                $loggerId = sprintf(self::MONOLOG_LOGGER_SERVICE_ID . '.%s', $tag['channel']);
                $loggers[$loggerId] = $container->findDefinition($loggerId);
            }
        }

        $nestedHandlerId = null;

        foreach ($loggers as $logger) {
            $calls = array_filter($logger->getMethodCalls(), function ($call) {
                return $call[0] == 'pushHandler';
            });

            for ($i = 0; $i < count($calls); $i++) {
                $handlerId = (string)$calls[$i][1][0];
                $handler = $container->findDefinition($handlerId);

                if ($handler->getClass() != DetailedLogsHandler::class) {
                    continue;
                }

                if ($i == 0) {
                    throw new InvalidConfigurationException(
                        'Detailed logger is not configured properly. Please specify nested handler'
                    );
                }

                $nestedHandlerId = $calls[$i - 1][1][0];

                unset($calls[$i - 1]);
                $logger->removeMethodCall('pushHandler')->setMethodCalls($calls);

                break;
            }
        }

        return $nestedHandlerId;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $nestedHandlerId
     */
    protected function injectNestedHandler(ContainerBuilder $container, $nestedHandlerId)
    {
        $detailedLogsHandler = $container->findDefinition(self::DETAILED_LOGS_HANDLER_SERVICE_ID);
        $detailedLogsHandler->addMethodCall('setHandler', [new Reference($nestedHandlerId)]);
    }
}
