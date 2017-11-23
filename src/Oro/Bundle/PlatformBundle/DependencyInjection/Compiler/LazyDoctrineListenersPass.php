<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Marks all Doctrine event listeners as lazy.
 */
class LazyDoctrineListenersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $listenerTagName = $this->getListenerTagName();
        $services = array_keys($container->findTaggedServiceIds($listenerTagName));
        foreach ($services as $serviceId) {
            $serviceDef = $container->getDefinition($serviceId);
            $tags = $serviceDef->getTag($listenerTagName);
            $serviceDef->clearTag($listenerTagName);
            $hasLazy = false;
            foreach ($tags as $tag) {
                if (!array_key_exists('lazy', $tag)) {
                    $tag['lazy'] = true;
                    $hasLazy = true;
                } elseif ($tag['lazy']) {
                    $hasLazy = true;
                }
                $serviceDef->addTag($listenerTagName, $tag);
            }
            if ($hasLazy) {
                $serviceDef->setPublic(true);
            }
        }
    }

    /**
     * @return string
     */
    protected function getListenerTagName()
    {
        return 'doctrine.event_listener';
    }
}
