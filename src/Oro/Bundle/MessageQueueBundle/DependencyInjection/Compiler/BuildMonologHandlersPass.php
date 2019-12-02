<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Monolog\Logger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Pass arguments to the `oro_message_queue.log.handler.console_error` service from monolog configuration
 */
class BuildMonologHandlersPass implements CompilerPassInterface
{
    const CONSOLE_ERROR_HANDLER_ID = 'oro_message_queue.log.handler.console_error';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig('monolog');
        foreach ($configs as $config) {
            if (array_key_exists('handlers', $config)) {
                foreach ($config['handlers'] as $name => $handler) {
                    if (!array_key_exists('id', $handler) || self::CONSOLE_ERROR_HANDLER_ID !== $handler['id']) {
                        continue;
                    }

                    $nestedHandlerId = sprintf('monolog.handler.%s', $handler['handler']);
                    $nestedHandlerDefinition = $container->getDefinition($nestedHandlerId);

                    $consoleErrorHandler = $container->getDefinition(self::CONSOLE_ERROR_HANDLER_ID);
                    $consoleErrorHandler->setArguments([
                        $nestedHandlerDefinition,
                        $this->getArgument($handler, 'buffer_size', 0),
                        $this->getArgument($handler, 'level', Logger::DEBUG),
                        $this->getArgument($handler, 'bubble', true),
                        $this->getArgument($handler, 'flush_on_overflow', false),
                    ]);
                }
            }
        }
    }

    /**
     * @param array $handler
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function getArgument(array $handler, $key, $default)
    {
        return array_key_exists($key, $handler) ? $handler[$key] : $default;
    }
}
