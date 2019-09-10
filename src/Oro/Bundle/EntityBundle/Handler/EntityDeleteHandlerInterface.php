<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * An interface for classes responsible to implement a business logic to delete an entity.
 */
interface EntityDeleteHandlerInterface
{
    /**
     * Checks whether the deletion of the given entity is granted.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isDeleteGranted($entity): bool;

    /**
     * Deletes the given entity.
     *
     * @param object $entity  The entity to be deleted
     * @param bool   $flush   Whether to call flush() method of an entity manager
     * @param array  $options The options for the delete operation
     *
     * @return array|null The options that should be passes to flush() method if $flush parameter is FALSE.
     *                    The array must have all options passed to the delete() method
     *                    and must have "entity" element that contains the entity to be deleted.
     *                    NULL if $flush parameter is TRUE
     *
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    public function delete($entity, bool $flush = true, array $options = []): ?array;

    /**
     * Flushed the deleted entity to the database by calling flush() method of an entity manager.
     *
     * @param array $options The options are returned by delete() method
     */
    public function flush(array $options): void;

    /**
     * Flushes all deleted entities to the database by calling flush() method of an entity manager.
     *
     * @param array $listOfOptions The array of options are returned by delete() method for each deleted entity
     */
    public function flushAll(array $listOfOptions): void;
}
