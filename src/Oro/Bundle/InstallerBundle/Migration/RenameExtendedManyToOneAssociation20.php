<?php

namespace Oro\Bundle\InstallerBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This class can be used to rename the class name of target entities for extended many-to-one associations
 * during migration to v2.0.
 */
class RenameExtendedManyToOneAssociation20
{
    /** @var Connection */
    private $connection;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    /** @var RenameExtension */
    private $renameExtension;

    /** @var ExtendExtension */
    private $extendExtension;

    /**
     * @param Connection                      $connection
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param RenameExtension                 $renameExtension
     * @param ExtendExtension                 $extendExtension
     */
    public function __construct(
        Connection $connection,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        RenameExtension $renameExtension,
        ExtendExtension $extendExtension
    ) {
        $this->connection = $connection;
        $this->nameGenerator = $nameGenerator;
        $this->renameExtension = $renameExtension;
        $this->extendExtension = $extendExtension;
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $entityClass
     * @param string   $associationKind
     */
    public function rename(
        Schema $schema,
        QueryBag $queries,
        $entityClass,
        $associationKind
    ) {
        $tableName = $this->extendExtension->getTableNameByEntityClass($entityClass);
        if ($tableName && !$schema->hasTable($tableName)) {
            return;
        }

        $targetClassNames = $this->loadTargetClassNames($entityClass);
        foreach ($targetClassNames as $targetClassName) {
            if (0 !== strpos($targetClassName, 'Oro\\')) {
                continue;
            }

            $targetTableName = $this->extendExtension->getTableNameByEntityClass($targetClassName);
            if (!$targetTableName) {
                continue;
            }

            $associationName = ExtendHelper::buildAssociationName($targetClassName, $associationKind);
            $columnName = $this->nameGenerator->generateRelationColumnName($associationName);

            $oldTargetClassName = 'OroCRM' . substr($targetClassName, 3);
            $oldAssociationName = ExtendHelper::buildAssociationName($oldTargetClassName, $associationKind);
            $oldColumnName = $this->nameGenerator->generateRelationColumnName($oldAssociationName);
            $oldForeignKeyName = $this->nameGenerator->generateForeignKeyConstraintName($tableName, [$oldColumnName]);

            $table = $schema->getTable($tableName);
            if ($table->hasColumn($oldColumnName) && !$table->hasColumn($columnName)) {
                if ($table->hasForeignKey($oldForeignKeyName)) {
                    $table->removeForeignKey($oldForeignKeyName);
                }
                $this->renameExtension->renameColumn($schema, $queries, $table, $oldColumnName, $columnName);
                $this->renameExtension->addForeignKeyConstraint(
                    $schema,
                    $queries,
                    $tableName,
                    $targetTableName,
                    [$columnName],
                    ['id'],
                    ['onDelete' => 'SET NULL']
                );
                $queries->addQuery(new UpdateExtendRelationQuery(
                    $entityClass,
                    $targetClassName,
                    $oldAssociationName,
                    $associationName,
                    RelationType::MANY_TO_ONE
                ));
            }
        }
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    private function loadTargetClassNames($entityClass)
    {
        $targetClassNames = [];
        $row = $this->connection->fetchAssoc(
            'SELECT data FROM oro_entity_config WHERE class_name = :class LIMIT 1',
            ['class' => $entityClass],
            ['class' => 'string']
        );
        if ($row) {
            $data = $this->connection->convertToPHPValue($row['data'], 'array');
            if (!empty($data['extend']['relation'])) {
                $relationPrefix = sprintf('manyToOne|%s|', $entityClass);
                $relations = $data['extend']['relation'];
                foreach ($relations as $relationName => $relation) {
                    if (0 === strpos($relationName, $relationPrefix)
                        && array_key_exists('owner', $relation)
                        && $relation['owner']
                        && array_key_exists('target_entity', $relation)
                    ) {
                        $targetClassNames[] = $relation['target_entity'];
                    }
                }
            }
        }

        return $targetClassNames;
    }
}
