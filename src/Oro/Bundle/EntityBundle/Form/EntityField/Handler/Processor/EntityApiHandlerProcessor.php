<?php

namespace Oro\Bundle\EntityBundle\Form\EntityField\Handler\Processor;

use Doctrine\Common\Util\ClassUtils;

/**
 * Processes entity field updates through registered API handlers.
 *
 * This processor manages a collection of entity API handlers and routes entity
 * field update operations to the appropriate handler based on the entity class.
 * It coordinates the pre-processing, validation, post-processing, and error handling
 * phases of entity field updates.
 */
class EntityApiHandlerProcessor
{
    /**
     * @var EntityApiHandlerInterface[]
     */
    protected $handlers = [];

    public function addHandler(EntityApiHandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @return EntityApiHandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Get handler by entity class
     *
     * @param string $class
     *
     * @return EntityApiHandlerInterface
     */
    public function getHandlerByClass($class)
    {
        /** @var EntityApiHandlerInterface $handler */
        foreach ($this->handlers as $handler) {
            if ($handler->getClass() === $class) {
                return $handler;
            }
        }

        return null;
    }

    public function preProcess($entity)
    {
        $handler = $this->getHandlerByClass(ClassUtils::getClass($entity));

        if ($handler) {
            $handler->preProcess($entity);
        }
    }

    public function beforeProcess($entity)
    {
        $handler = $this->getHandlerByClass(ClassUtils::getClass($entity));

        if ($handler) {
            $handler->beforeProcess($entity);
        }
    }

    public function afterProcess($entity)
    {
        $handler = $this->getHandlerByClass(ClassUtils::getClass($entity));

        if ($handler) {
            return $handler->afterProcess($entity);
        }

        return false;
    }

    public function invalidateProcess($entity)
    {
        $handler = $this->getHandlerByClass(ClassUtils::getClass($entity));

        if ($handler) {
            $handler->invalidateProcess($entity);
        }
    }
}
