<?php

namespace Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class EventTriggerExtensionCompilerPass implements CompilerPassInterface
{
    const LISTENER_SERVICE = 'oro_workflow.listener.process_collector';
    const EXTENSION_TAG = 'oro_workflow.listener.process_collector.extension';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadExtensions($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadExtensions(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::LISTENER_SERVICE)) {
            $service = $container->getDefinition(self::LISTENER_SERVICE);
            $extensions = $container->findTaggedServiceIds(self::EXTENSION_TAG);

            foreach ($extensions as $id => $attributes) {
                $service->addMethodCall('addExtension', [new Reference($id)]);
            }
        }
    }
}
