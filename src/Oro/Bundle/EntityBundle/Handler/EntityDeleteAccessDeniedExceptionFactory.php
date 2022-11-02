<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The factory that is used to create AccessDeniedException
 * that should be thrown when a deletion of an entity is denied.
 */
class EntityDeleteAccessDeniedExceptionFactory
{
    /**
     * Creates AccessDeniedException that should be thrown when a deletion of an entity is denied.
     */
    public function createAccessDeniedException(string $reason = 'access denied'): AccessDeniedException
    {
        if (!str_ends_with($reason, '.')) {
            $reason .= '.';
        }

        return new AccessDeniedException(sprintf('The delete operation is forbidden. Reason: %s', $reason));
    }
}
