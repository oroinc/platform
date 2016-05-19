<?php
namespace Oro\Bundle\MessagingBundle\DependencyInjection\Compiler;

use Oro\Component\Messaging\ZeroConfig\Route;
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
        if (false == $container->hasDefinition('oro_messaging.zeroconfig.route_registry')) {
            return;
        }

        $tagName = 'oro_messaging.zeroconfig.message_processor';

        $routeRegistryDef = $container->getDefinition('oro_messaging.zeroconfig.route_registry');

        foreach ($container->findTaggedServiceIds($tagName) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['messageName']) || false == $tagAttribute['messageName']) {
                    throw new \LogicException(sprintf('Message name is not set but it is required. service: "%s", tag: "%s"', $serviceId, $tagName));
                }

                $queueName = null;
                if (isset($tagAttribute['queueName']) && $tagAttribute['queueName']) {
                    $queueName = $tagAttribute['queueName'];
                }

                $processorName = $serviceId;
                if (isset($tagAttribute['processorName']) && $tagAttribute['processorName']) {
                    $processorName = $tagAttribute['processorName'];
                }

                $routeDef = new Definition(Route::class);
                $routeDef->setPublic(false);
                $routeDef->addMethodCall('setMessageName', [$tagAttribute['messageName']]);
                $routeDef->addMethodCall('setProcessorName', [$processorName]);
                $routeDef->addMethodCall('setQueueName', [$queueName]);

                $routeRegistryDef->addMethodCall('addRoute', [$tagAttribute['messageName'], $routeDef]);
            }
        }
    }
}
