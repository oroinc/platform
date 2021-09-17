<?php

namespace Oro\Bundle\EntityExtendBundle\Exception;

use Oro\Bundle\EntityBundle\Exception\RuntimeException;
use Throwable;

/**
 * This exception throws within the process of schema update after we've done a rollback of all changes in the schema.
 */
class RecoverableUpdateSchemaException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            'Caught exception while running update the database schema. All changes in the schema were reverted.',
            0,
            $previous
        );
    }
}
