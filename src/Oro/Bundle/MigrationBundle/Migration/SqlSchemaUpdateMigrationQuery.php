<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * Marks SQL queries as schema update queries during migrations.
 *
 * This class extends {@see SqlMigrationQuery} and implements {@see SchemaUpdateQuery} to indicate that
 * the SQL queries being executed are schema updates. This allows the migration system to
 * properly categorize and handle these queries as part of the schema update process.
 */
class SqlSchemaUpdateMigrationQuery extends SqlMigrationQuery implements SchemaUpdateQuery
{
    #[\Override]
    public function isUpdateRequired()
    {
        return true;
    }
}
