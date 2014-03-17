<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Table;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class IndexLimitExtension implements DatabasePlatformAwareInterface, NameGeneratorAwareInterface
{
    const MAX_INDEX_SIZE = 767;

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
     * @param string[] $columnNames Values are limits, indexes are columns
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
            $size = array_sum($columnNames);

            if ($size > self::MAX_INDEX_SIZE) {
                throw new \RuntimeException(
                    sprintf('Index size is %d, maximum is %d', $size, self::MAX_INDEX_SIZE)
                );
            }

            $columnsString = $this->getColumnsString($columnNames);
            $queries->addPostQuery(
                "ALTER TABLE `$tableName` ADD INDEX `$indexName` ($columnsString);"
            );
        } else {
            $table->addIndex(array_keys($columnNames), $indexName, []);
        }
    }

    /**
     * @param array $columnNames
     * @return string
     */
    protected function getColumnsString(array $columnNames)
    {
        array_walk(
            $columnNames,
            function (&$limit, $columnName) {
                $limit = $columnName . '(' . $limit . ')';
            }
        );

        return implode($columnNames, ',');
    }
}
