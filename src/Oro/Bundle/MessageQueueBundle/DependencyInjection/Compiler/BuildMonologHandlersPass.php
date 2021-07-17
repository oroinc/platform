<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Monolog\Logger;
use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Pass arguments to the `oro_message_queue.log.handler.console_error` service from monolog configuration
 */
class BuildMonologHandlersPass implements CompilerPassInterface
{
    private const CONSOLE_ERROR_HANDLER_ID = 'oro_message_queue.log.handler.console_error';
    private const VERBOSITY_FILTER_HANDLER_ID = 'oro_message_queue.log.handler.verbosity_filter';

    public function process(ContainerBuilder $container)
    {
        $extension = $container->getExtension('monolog');
        if ($extension instanceof MonologExtension) {
            $configs = $container->getExtensionConfig('monolog');
            $configuration = $extension->getConfiguration($configs, $container);
            $config = (new Processor())->processConfiguration($configuration, $configs);

            if (isset($config['handlers'])) {
                foreach ($config['handlers'] as $handler) {
                    if ('service' !== $handler['type']) {
                        continue;
                    }

                    $this->buildHandler($container, $handler);
                }
            }
        }
    }

    private function buildHandler(ContainerBuilder $container, array $handler): void
    {
        if (!in_array($handler['id'], [self::CONSOLE_ERROR_HANDLER_ID, self::VERBOSITY_FILTER_HANDLER_ID], true)) {
            return;
        }

        $handlerDefinition = $container->getDefinition($handler['id']);

        $nestedHandlerId = sprintf('monolog.handler.%s', $handler['handler']);
        $handlerDefinition->setArgument(1, new Reference($nestedHandlerId));

        switch ($handler['id']) {
            case self::CONSOLE_ERROR_HANDLER_ID:
                $handlerDefinition->setArgument(2, $this->getArgument($handler, 'level', Logger::DEBUG));
                break;
            case self::VERBOSITY_FILTER_HANDLER_ID:
                $handlerDefinition->setArgument(2, $this->getArgument($handler, 'verbosity_levels', []));
                $handlerDefinition->addTag('kernel.event_subscriber');
                break;
        }
    }

    /**
     * @param array $handler
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private function getArgument(array $handler, string $key, $default)
    {
        return array_key_exists($key, $handler) ? $handler[$key] : $default;
    }
}
