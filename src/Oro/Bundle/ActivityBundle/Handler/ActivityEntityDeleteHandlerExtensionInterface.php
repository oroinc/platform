<?php

namespace Oro\Bundle\ActivityBundle\Handler;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * An interface for classes that provides an extended business logic to delete activity entity associations.
 */
interface ActivityEntityDeleteHandlerExtensionInterface
{
    /**
     * Checks if a delete operation is granted.
     *
     * @param object $entity
     * @param object $targetEntity
     *
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    public function assertDeleteGranted($entity, $targetEntity): void;
}
