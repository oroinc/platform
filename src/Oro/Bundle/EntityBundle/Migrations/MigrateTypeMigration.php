<?php

namespace Oro\Bundle\EntityBundle\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateTypeMigration implements DatabasePlatformAwareInterface, ContainerAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @var ForeignKeyConstraint[]
     */
    protected $foreignKeys = [];

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $tableName
     * @param string   $columnName
     * @param string   $type
     */
    public function changeType(Schema $schema, QueryBag $queries, $tableName, $columnName, $type)
    {
        /** Apply only for mysql platform */
        if ($this->platform->getName() !== DatabasePlatformInterface::DATABASE_MYSQL) {
            return;
        }

        /** Already applied */
        $targetColumn = $schema->getTable($tableName)->getColumn($columnName);

        $type = Type::getType($type);
        if ($targetColumn->getType() === $type) {
            return;
        }

        $relatedColumnsData = $this->getRelatedColumnsData($schema, $tableName, $columnName);

        foreach ($relatedColumnsData as $relatedColumnData) {
            $relatedTable      = $schema->getTable($relatedColumnData['tableName']);
            $relatedColumn     = $relatedTable->getColumn($relatedColumnData['columnName']);
            $relatedForeignKey = $relatedTable->getForeignKey($relatedColumnData['constraintName']);

            $this->foreignKeys[$relatedForeignKey->getName()] = $relatedForeignKey;

            $queries->addPreQuery(
                $this->platform->getDropForeignKeySQL($relatedForeignKey, $relatedTable)
            );
            $relatedColumn->setType($type);
        }

        $targetColumn->setType($type);

        foreach ($this->foreignKeys as $foreignKey) {
            $queries->addPostQuery(
                $this->platform->getCreateForeignKeySQL($foreignKey, $foreignKey->getLocalTable())
            );
        }

        $this->foreignKeys = [];
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string $columnName
     *
     * @return array
     */
    protected function getRelatedColumnsData(Schema $schema, $tableName, $columnName)
    {
        $query = <<<SQL
SELECT
    TABLE_NAME as tableName,
    COLUMN_NAME as columnName,
    CONSTRAINT_NAME as constraintName
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = :schemaName
    AND REFERENCED_TABLE_NAME = :tableName
    AND REFERENCED_COLUMN_NAME = :columnName
SQL;

        return $this
            ->container
            ->get('doctrine.orm.entity_manager')
            ->getConnection()
            ->fetchAll(
                $query,
                [
                    'schemaName' => $schema->getName(),
                    'tableName'  => $tableName,
                    'columnName' => $columnName
                ],
                [
                    'schemaName' => 'string',
                    'tableName'  => 'string',
                    'columnName' => 'string'
                ]
            );
    }
}
