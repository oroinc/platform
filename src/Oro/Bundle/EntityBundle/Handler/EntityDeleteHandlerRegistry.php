<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Psr\Container\ContainerInterface;

/**
 * The registry that allows to get a handler responsible to implement
 * a business logic to delete a specific entity type.
 */
class EntityDeleteHandlerRegistry
{
    /** @var ContainerInterface */
    private $handlerContainer;

    public function __construct(ContainerInterface $handlerContainer)
    {
        $this->handlerContainer = $handlerContainer;
    }

    /**
     * Gets a handler responsible to delete the given entity type.
     */
    public function getHandler(string $entityClass): EntityDeleteHandlerInterface
    {
        if ($this->handlerContainer->has($entityClass)) {
            return $this->handlerContainer->get($entityClass);
        }

        return $this->handlerContainer->get('default');
    }
}
