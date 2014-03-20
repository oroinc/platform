<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Table;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class IndexLimitExtension implements DatabasePlatformAwareInterface, NameGeneratorAwareInterface
{
    const MAX_INDEX_SIZE = 255;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * @param QueryBag $queries
     * @param Table    $table
     * @param string[] $columnNames
     * @param string   $indexName
     * @throws \Exception
     */
    public function addLimitedIndex(QueryBag $queries, Table $table, $columnNames, $indexName = null)
    {
        $tableName = $table->getName();
        if (!$indexName) {
            $indexName = $this->nameGenerator->generateIndexName($tableName, $columnNames);
        }

        if ($this->platform instanceof MySqlPlatform) {
            $columnsString = $this->getColumnsString($table, $columnNames);
            $queries->addPostQuery(
                "ALTER TABLE `$tableName` ADD INDEX `$indexName` ($columnsString);"
            );
        } else {
            $table->addIndex($columnNames, $indexName, []);
        }
    }

    /**
     * @param Table $table
     * @param array $columnNames
     * @return string
     */
    protected function getColumnsString(Table $table, array $columnNames)
    {
        array_walk(
            $columnNames,
            function (&$columnName) use ($table) {
                $columnLength = $table->getColumn($columnName)->getLength();
                if ($columnLength > self::MAX_INDEX_SIZE) {
                    $columnName = $columnName . '(' . self::MAX_INDEX_SIZE . ')';
                }
            }
        );

        return implode($columnNames, ',');
    }
}
