<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * A base class for compiler passes that add services to the list of persistent services.
 */
abstract class RegisterPersistentServicesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $persistentServices = $this->getPersistentServices($container);
        if (!empty($persistentServices)) {
            $container
                ->getDefinition('oro_message_queue.consumption.container_clearer')
                ->addMethodCall('setPersistentServices', [$persistentServices]);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    abstract protected function getPersistentServices(ContainerBuilder $container);
}
