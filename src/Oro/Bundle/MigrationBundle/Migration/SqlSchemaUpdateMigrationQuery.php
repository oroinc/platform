<?php

namespace Oro\Bundle\MigrationBundle\Migration;

class SqlSchemaUpdateMigrationQuery extends SqlMigrationQuery implements SchemaUpdateQuery
{
    #[\Override]
    public function isUpdateRequired()
    {
        return true;
    }
}
