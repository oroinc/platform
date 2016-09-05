<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * Schema update query interface defines whether need to update
 * schema object after execution of a migration that includes query
 * that implements this interface.
 */
interface SchemaUpdateQuery
{
    /**
     * Whether need to update schema after execution
     *
     * @return bool
     */
    public function isUpdateRequired();
}
