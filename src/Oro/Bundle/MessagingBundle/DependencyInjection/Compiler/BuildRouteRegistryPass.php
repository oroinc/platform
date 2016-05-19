<?php
namespace Oro\Bundle\MessagingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildRouteRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'oro_messaging.zero_config.message_processor';
        $routeRegistryId = 'oro_messaging.zero_config.route_registry';

        if (false == $container->hasDefinition($routeRegistryId)) {
            return;
        }

        $configs = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['topicName']) || false == $tagAttribute['topicName']) {
                    throw new \LogicException(sprintf('Topic name is not set but it is required. service: "%s", tag: "%s"', $serviceId, $processorTagName));
                }

                $config = [];
                $config['processor'] = $serviceId;

                if (isset($tagAttribute['processorName']) && $tagAttribute['processorName']) {
                    $config['processor'] = $tagAttribute['processorName'];
                }

                if (isset($tagAttribute['queueName']) && $tagAttribute['queueName']) {
                    $config['queue'] = $tagAttribute['queueName'];
                }

                $configs[$tagAttribute['topicName']][] = $config;
            }
        }

        $routeRegistryDef = $container->getDefinition($routeRegistryId);
        $routeRegistryDef->addMethodCall('setRoutesConfig', [$configs]);
    }
}
