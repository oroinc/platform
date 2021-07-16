<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Pass arguments to the `oro_message_queue.log.handler.verbosity_filter` service from monolog configuration
 */
class BuildMonologHandlersPass implements CompilerPassInterface
{
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
        if ($handler['id'] !== self::VERBOSITY_FILTER_HANDLER_ID) {
            return;
        }

        $handlerDefinition = $container->getDefinition($handler['id']);

        $nestedHandlerId = sprintf('monolog.handler.%s', $handler['handler']);
        $handlerDefinition->setArgument(1, new Reference($nestedHandlerId));
        $handlerDefinition->setArgument(2, $this->getArgument($handler, 'verbosity_levels', []));
        $handlerDefinition->addTag('kernel.event_subscriber');
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
