<?php

namespace Oro\Bundle\EntityBundle\Migrations\Extension;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendColumn;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Provides an ability to change data type for a table primary key.
 */
class ChangeTypeExtension implements DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    public function changePrimaryKeyType(
        Schema $schema,
        QueryBag $queries,
        string $tableName,
        string $columnName,
        string $typeName
    ): void {
        $targetColumn = $schema->getTable($tableName)->getColumn($columnName);
        $type = Type::getType($typeName);
        if ($targetColumn->getType() === $type) {
            return;
        }

        /** @var ForeignKeyConstraint[] $foreignKeys */
        $foreignKeys = [];

        foreach ($schema->getTables() as $table) {
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
