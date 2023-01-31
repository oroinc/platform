<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get a factory that creates a flush data handler for a batch operation
 * for a specific entity class.
 */
class BatchFlushDataHandlerFactoryRegistry
{
    /** @var array [[factory service id, entity class name], ...] */
    private array $factories;
    private ContainerInterface $container;

    /**
     * @param array              $factories [[factory service id, entity class name], ...]
     * @param ContainerInterface $container
     */
    public function __construct(array $factories, ContainerInterface $container)
    {
        $this->factories = $factories;
        $this->container = $container;
    }

    /**
     * Returns a factory that creates a flush data handler for a batch operation for the given entity class.
     *
     * @throws \LogicException if a factory does not exist for the given entity class
     */
    public function getFactory(string $entityClass): BatchFlushDataHandlerFactoryInterface
    {
        foreach ($this->factories as [$serviceId, $className]) {
            if (!$className || is_a($entityClass, $className, true)) {
                return $this->container->get($serviceId);
            }
        }
        throw new \LogicException(sprintf('Cannot find a factory for "%s".', $entityClass));
    }
}
