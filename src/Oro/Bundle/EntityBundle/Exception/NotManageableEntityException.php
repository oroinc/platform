<?php

namespace Oro\Bundle\EntityBundle\Exception;

class NotManageableEntityException extends RuntimeException
{
    public function __construct($className)
    {
        parent::__construct(sprintf('Entity class "%s" is not manageable.', $className));
    }
}
