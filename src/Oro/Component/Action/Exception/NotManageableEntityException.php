<?php

namespace Oro\Component\Action\Exception;

/**
 * Exception thrown if entity is not manageable.
 */
class NotManageableEntityException extends RuntimeException
{
    /**
     * @param string $className
     */
    public function __construct($className)
    {
        parent::__construct(sprintf('Entity class "%s" is not manageable.', $className));
    }
}
