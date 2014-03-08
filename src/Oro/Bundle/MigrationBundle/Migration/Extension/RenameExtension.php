<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

class RenameExtension
{
    /**
     * Renames table
     *
     * @param string $oldTableName
     * @param string $newTableName
     * @return string
     */
    public function getRenameTableQuery($oldTableName, $newTableName)
    {
        return sprintf('ALTER TABLE %s RENAME TO %s;', $oldTableName, $newTableName);
    }
}
