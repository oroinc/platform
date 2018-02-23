<?php

namespace Oro\Bundle\EntityBundle\Migrations\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeTypeExtension implements DatabasePlatformAwareInterface
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
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $tableName
     * @param string   $columnName
     * @param string   $type
     *
     * @throws \Exception
     */
    public function changePrimaryKeyType(Schema $schema, QueryBag $queries, $tableName, $columnName, $type)
    {
        $targetColumn = $schema->getTable($tableName)->getColumn($columnName);
        $type         = Type::getType($type);

        if ($targetColumn->getType() === $type) {
            return;
        }

        /** @var ForeignKeyConstraint[] $foreignKeys */
        $foreignKeys = [];

        foreach ($schema->getTables() as $table) {
            /** @var ForeignKeyConstraint[] $tableForeignKeys */
            $tableForeignKeys = array_filter(
                $table->getForeignKeys(),
                function (ForeignKeyConstraint $tableForeignKey) use ($tableName, $columnName) {
                    if ($tableForeignKey->getForeignTableName() !== $tableName) {
                        return false;
                    }

                    return $tableForeignKey->getForeignColumns() === [$columnName];
                }
            );

            foreach ($tableForeignKeys as $tableForeignKey) {
                $foreignKeys[$tableForeignKey->getName()] = $tableForeignKey;

                $foreignKeyTableName   = $tableForeignKey->getLocalTable()->getName();
                $foreignKeyColumnNames = $tableForeignKey->getLocalColumns();

                $queries->addPreQuery(
                    $this->platform->getDropForeignKeySQL($tableForeignKey, $foreignKeyTableName)
                );

                $column = $schema->getTable($foreignKeyTableName)->getColumn(reset($foreignKeyColumnNames));
                if ($column instanceof ExtendColumn) {
                    $column
                        ->disableExtendOptions()
                        ->setType($type)
                        ->enableExtendOptions();
                } else {
                    $column->setType($type);
                }
            }
        }

        $targetColumn->setType($type);

        foreach ($foreignKeys as $foreignKey) {
            $queries->addPostQuery(
                $this->platform->getCreateForeignKeySQL($foreignKey, $foreignKey->getLocalTable())
            );
        }
    }
}
