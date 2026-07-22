<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * Describes interface for bulk insert no conflict query executors
 */
interface InsertNoConflictQueryExecutorInterface extends InsertQueryExecutorInterface
{
    public function setOnConflictIgnoredFields(array $onConflictIgnoredFields): void;
}
