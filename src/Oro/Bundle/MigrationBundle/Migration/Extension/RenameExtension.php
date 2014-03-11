<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Schema\Column;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class RenameExtension implements DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Renames a table
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $oldTableName
     * @param string   $newTableName
     */
    public function renameTable(Schema $schema, QueryBag $queries, $oldTableName, $newTableName)
    {
        $table         = $schema->getTable($oldTableName);
        $diff          = new TableDiff($table->getName());
        $diff->newName = $newTableName;

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }

    /**
     * Renames a column
     *
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param Table    $table
     * @param string   $oldColumnName
     * @param string   $newColumnName
     */
    public function renameColumn(Schema $schema, QueryBag $queries, Table $table, $oldColumnName, $newColumnName)
    {
        $column = new Column(['column' => $table->getColumn($oldColumnName)]);
        $column->changeName($newColumnName);
        $diff                 = new TableDiff($table->getName());
        $diff->renamedColumns = [$oldColumnName => $column];

        $renameQuery = new SqlMigrationQuery(
            $this->platform->getAlterTableSQL($diff)
        );

        $queries->addQuery($renameQuery);
    }
}
