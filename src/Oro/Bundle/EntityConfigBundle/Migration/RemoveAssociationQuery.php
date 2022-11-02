<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\SchemaUpdateQuery;
use Psr\Log\LoggerInterface;

/**
 * Removes an association relation, cleans entity config, drops the table column and the relation table.
 */
abstract class RemoveAssociationQuery extends ParametrizedMigrationQuery implements SchemaUpdateQuery
{
    protected string $sourceEntityClass;

    protected string $targetEntityClass;

    protected string $associationKind;

    protected string $relationType;

    protected bool $dropRelationColumnsAndTables;

    /**
     * Must be specified if $dropRelationColumnsAndTables is true and relation type is "manyToOne".
     */
    protected string $sourceTableName;

    /**
     * Must be specified if $dropRelationColumnsAndTables is true and relation type is "manyToOne".
     */
    protected string $targetTableName;

    private bool $isSchemaUpdateRequired = false;

    public function getDescription(): string
    {
        return \sprintf(
            'Remove association relation from %s entity to %s (association kind: %s, relation type: %s, '
            . 'drop relation column/table: %s, source table: %s, target table: %s).',
            $this->sourceEntityClass,
            $this->targetEntityClass,
            $this->associationKind,
            $this->relationType,
            $this->dropRelationColumnsAndTables ? 'yes' : 'no',
            $this->dropRelationColumnsAndTables ? ($this->sourceTableName ?? 'n/a') : 'n/a',
            $this->dropRelationColumnsAndTables ? ($this->targetTableName ?? 'n/a') : 'n/a',
        );
    }

    /**
     * @throws \Doctrine\DBAL\DBALException on any database errors
     * @throws \LogicException if the source entity is not a configurable entity
     */
    public function execute(LoggerInterface $logger): void
    {
        $sourceEntityRow = $this->connection->fetchAssoc(
            'SELECT e.id, e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1',
            [$this->sourceEntityClass]
        );
        if (!$sourceEntityRow) {
            throw new \LogicException(\sprintf(
                'Source entity %s is not a configurable entity.',
                $this->sourceEntityClass
            ));
        }
        $sourceEntityData = $this->connection->convertToPHPValue($sourceEntityRow['data'], Types::ARRAY);

        $fieldName = ExtendHelper::buildAssociationName(
            $this->targetEntityClass,
            $this->associationKind
        );

        $relationKey = ExtendHelper::buildRelationKey(
            $this->sourceEntityClass,
            $fieldName,
            $this->relationType,
            $this->targetEntityClass
        );

        unset(
            $sourceEntityData['extend']['relation'][$relationKey],
            $sourceEntityData['extend']['schema']['relation'][$fieldName],
            $sourceEntityData['extend']['schema']['addremove'][$fieldName],
            $sourceEntityData['extend']['schema']['default'][ExtendConfigDumper::DEFAULT_PREFIX . $fieldName]
        );
        $logger->info(\var_export($sourceEntityData, true));

        $sql = 'UPDATE oro_entity_config SET data = :data WHERE class_name = :class_name';
        $params = ['data' => $sourceEntityData, 'class_name' => $this->sourceEntityClass];
        $types = ['data' => Types::ARRAY, 'class_name' => Types::STRING];
        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeStatement($sql, $params, $types);

        $sql = 'DELETE FROM oro_entity_config_field WHERE entity_id = :entity_id AND field_name = :field_name';
        $params = ['entity_id' => $sourceEntityRow['id'], 'field_name' => $fieldName];
        $types = ['entity_id' => Types::INTEGER, 'field_name' => Types::STRING];
        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeStatement($sql, $params, $types);

        if ($this->dropRelationColumnsAndTables) {
            $this->dropRelationshipColumnsAndTables($fieldName, $logger);
        }
    }

    public function isUpdateRequired(): bool
    {
        return $this->isSchemaUpdateRequired;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function dropRelationshipColumnsAndTables(string $fieldName, LoggerInterface $logger): void
    {
        $nameGenerator = new ExtendDbIdentifierNameGenerator();
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $this->connection->getSchemaManager();
        if (RelationType::MANY_TO_ONE === $this->relationType) {
            $targetTable = $schemaManager->listTableDetails($this->targetTableName);
            $sourceColumnName = $nameGenerator->generateRelationColumnName(
                $fieldName,
                '_' . $targetTable->getPrimaryKeyColumns()[0]
            );
            $sourceTable = $schemaManager->listTableDetails($this->sourceTableName);
            if ($sourceTable->hasColumn($sourceColumnName)) {
                $foreignKeys = $sourceTable->getForeignKeys();
                foreach ($foreignKeys as $foreignKey) {
                    if (\in_array($sourceColumnName, $foreignKey->getUnquotedLocalColumns())) {
                        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
                            $sql = \sprintf(
                                'ALTER TABLE %s DROP FOREIGN KEY %s',
                                $this->sourceTableName,
                                $this->connection->quoteIdentifier($foreignKey->getName())
                            );
                        } else {
                            $sql = \sprintf(
                                'ALTER TABLE %s DROP CONSTRAINT %s',
                                $this->sourceTableName,
                                $this->connection->quoteIdentifier($foreignKey->getName())
                            );
                        }
                        $this->logQuery($logger, $sql);
                        $this->connection->executeQuery($sql);
                        $this->isSchemaUpdateRequired = true;
                    }
                }
                $sql = \sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $this->sourceTableName,
                    $this->connection->quoteIdentifier($sourceColumnName)
                );
                $this->logQuery($logger, $sql);
                $this->connection->executeQuery($sql);
                $this->isSchemaUpdateRequired = true;
            }
        } elseif (RelationType::MANY_TO_MANY === $this->relationType) {
            $joinTableName = $nameGenerator->generateManyToManyJoinTableName(
                $this->sourceEntityClass,
                $fieldName,
                $this->targetEntityClass
            );
            if (\in_array($joinTableName, $schemaManager->listTableNames())) {
                $sql = \sprintf('DROP TABLE %s', $joinTableName);
                $this->logQuery($logger, $sql);
                $this->connection->executeQuery($sql);
                $this->isSchemaUpdateRequired = true;
            }
        }
    }
}
