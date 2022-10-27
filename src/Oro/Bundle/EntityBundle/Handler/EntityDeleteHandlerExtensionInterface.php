<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * An interface for classes that provides an extended business logic to delete an entity.
 */
interface EntityDeleteHandlerExtensionInterface
{
    /**
     * Checks if a delete operation is granted.
     *
     * @param object $entity
     *
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    public function assertDeleteGranted($entity): void;

    /**
     * Preforms additional operations after the entity was deleted and flushed to the database.
     *
     * @param object $entity  The entity to be deleted
     * @param array  $options The options are returned by EntityDeleteHandlerInterface::delete() method
     */
    public function postFlush($entity, array $options): void;
}
