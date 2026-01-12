<?php

namespace Oro\Bundle\EntityBundle\Exception;

/**
 * Thrown when an entity class is not managed by Doctrine.
 *
 * This exception indicates that the specified entity class is not configured
 * as a Doctrine-managed entity and cannot be used with ORM operations.
 */
class NotManageableEntityException extends RuntimeException implements EntityExceptionInterface
{
    public function __construct($className)
    {
        parent::__construct(sprintf('Entity class "%s" is not manageable.', $className));
    }
}
