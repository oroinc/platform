<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;

/**
 * The handler that is used to delete an entity via action configuration.
 */
class DeleteHandler
{
    /** @var EntityDeleteHandlerRegistry */
    private $deleteHandlerRegistry;

    public function __construct(EntityDeleteHandlerRegistry $deleteHandlerRegistry)
    {
        $this->deleteHandlerRegistry = $deleteHandlerRegistry;
    }

    /**
     * @param object $entity
     */
    public function handleDelete($entity): void
    {
        $this->deleteHandlerRegistry
            ->getHandler(ClassUtils::getClass($entity))
            ->delete($entity);
    }
}
