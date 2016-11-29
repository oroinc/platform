<?php

namespace Oro\Bundle\NoteBundle\Migrations\Schema\v1_3;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateAssociationKindQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * Class names mapping for case if entity was renamed
     *
     * @var array
     */
    protected $oldClassNames = [];

    /**
     * @param Schema                          $schema
     * @param ActivityExtension               $activityExtension
     * @param ExtendExtension                 $extendExtension
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        Schema $schema,
        ActivityExtension $activityExtension,
        ExtendExtension $extendExtension,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->schema = $schema;
        $this->activityExtension = $activityExtension;
        $this->extendExtension = $extendExtension;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update Note Activity association kind';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $fromSchema = clone $this->schema;

        $noteEntityConfig = $this->getNoteEntityConfig($logger);

        $entitiesForDataMigration = [];
        $entityConfigs = $this->getApplicableEntityConfigs($logger);
        foreach ($entityConfigs as $entityConfigurationRow) {
            $targetEntityClassName = $entityConfigurationRow['class_name'];
            $targetTableName = $this->extendExtension->getTableNameByEntityClass($targetEntityClassName);
            $noteAssociationColumnName = $this->getNoteAssociationColumnName($targetEntityClassName);
            if (!$targetTableName
                || !$this->schema->hasTable($targetTableName)
                || !$this->schema->getTable('oro_note')->hasColumn($noteAssociationColumnName)) {
                continue;
            }
            $entitiesForDataMigration[$targetEntityClassName] = $targetTableName;

            $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', $targetTableName);
            if (!$this->schema->hasTable($associationTableName)) {
                $this->activityExtension->addActivityAssociation($this->schema, 'oro_note', $targetTableName);
            }

            $this->removeOldNoteAssociation($logger, $targetEntityClassName, $noteEntityConfig);

            unset($entityConfigurationRow['data']['note']['enabled']);
            $this->saveEntityConfig($logger, $entityConfigurationRow);
        }

        $this->saveEntityConfig($logger, $noteEntityConfig);
        $this->applyActivityAssociationSchemaChanges($logger, $fromSchema);

        foreach ($entitiesForDataMigration as $targetEntityClassName => $targetTableName) {
            $this->migrateNoteRelationDataToActivityRelationKind($targetTableName, $targetEntityClassName);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getApplicableEntityConfigs(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $entityConfigs = $this->connection->fetchAll($sql);
        $this->logQuery($logger, $sql);

        $targetEntitiesConfigs = [];
        foreach ($entityConfigs as $entityConfig) {
            $entityConfig['data'] = empty($entityConfig['data'])
                ? []
                : $this->connection->convertToPHPValue($entityConfig['data'], Type::TARRAY);

            if (!empty($entityConfig['data']['note']['enabled'])) {
                $targetEntitiesConfigs[] = $entityConfig;
            }
        }

        return $targetEntitiesConfigs;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getNoteEntityConfig(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config WHERE class_name=?';
        $noteEntityConfig = $this->connection->fetchAssoc($sql, ['Oro\Bundle\NoteBundle\Entity\Note']);
        $this->logQuery($logger, $sql);
        $noteEntityConfig['data'] = $this->connection->convertToPHPValue($noteEntityConfig['data'], Type::TARRAY);

        return $noteEntityConfig;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $targetEntityClassName
     * @param array           $noteEntityConfig
     */
    protected function removeOldNoteAssociation(
        LoggerInterface $logger,
        $targetEntityClassName,
        array &$noteEntityConfig
    ) {
        $noteAssociationName = $this->getNoteAssociationName($targetEntityClassName);
        $sql = 'DELETE FROM oro_entity_config_field WHERE field_name=? AND entity_id=?';
        $parameters = [$noteAssociationName, $noteEntityConfig['id']];
        $this->connection->executeUpdate($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);
        unset($noteEntityConfig['data']['extend']['schema']['relation'][$noteAssociationName]);

        $noteRelationClassName = empty($this->oldClassNames[$targetEntityClassName])
            ? $targetEntityClassName
            : $this->oldClassNames[$targetEntityClassName];
        $relationKeyName = ExtendHelper::buildRelationKey(
            'Oro\Bundle\NoteBundle\Entity\Note',
            $noteAssociationName,
            'manyToOne',
            $noteRelationClassName
        );
        unset($noteEntityConfig['data']['extend']['relation'][$relationKeyName]);
    }

    /**
     * @param LoggerInterface $logger
     * @param array           $entityConfig
     */
    protected function saveEntityConfig(LoggerInterface $logger, array $entityConfig)
    {
        $sql = 'UPDATE oro_entity_config SET `data`=? WHERE id=?';
        $parameters = [
            $this->connection->convertToDatabaseValue($entityConfig['data'], Type::TARRAY),
            $entityConfig['id']
        ];
        $this->connection->executeUpdate($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);
    }

    /**
     * @param LoggerInterface $logger
     * @param                 $fromSchema
     */
    protected function applyActivityAssociationSchemaChanges(LoggerInterface $logger, $fromSchema)
    {
        $comparator = new Comparator();
        $platform = $this->connection->getDatabasePlatform();
        $schemaDiff = $comparator->compare($fromSchema, $this->schema);
        $queries = $schemaDiff->toSql($platform);
        foreach ($queries as $query) {
            $this->logQuery($logger, $query);
            $this->connection->executeQuery($query);
        }
    }

    /**\
     * @param string $targetTable
     * @param string $targetClass
     */
    protected function migrateNoteRelationDataToActivityRelationKind($targetTable, $targetClass)
    {
        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', $targetTable);

        $associationColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName($targetClass);

        $noteAssociationColumnName = $this->getNoteAssociationColumnName($targetClass);
        $sql = <<<SQL
          INSERT INTO $associationTableName (note_id, $associationColumnName)
          SELECT id, $noteAssociationColumnName
          FROM oro_note WHERE $noteAssociationColumnName IS NOT NULL
SQL;
        $this->connection->executeUpdate($sql);

        $schemaManager = $this->connection->getSchemaManager();
        foreach ($schemaManager->listTableForeignKeys('oro_note') as $foreignKey) {
            if (in_array($noteAssociationColumnName, $foreignKey->getColumns())) {
                $schemaManager->dropForeignKey($foreignKey, 'oro_note');
            }
        }

        $sql = "ALTER TABLE oro_note DROP COLUMN {$noteAssociationColumnName}";
        $this->connection->executeUpdate($sql);
    }

    /**
     * @param string $targetClass
     *
     * @return string
     */
    protected function getNoteAssociationColumnName($targetClass)
    {
        $noteAssociationName = $this->getNoteAssociationName($targetClass);
        $noteAssociationColumnName = $this->nameGenerator->generateRelationColumnName($noteAssociationName);

        return $noteAssociationColumnName;
    }

    /**
     * @param string $targetClass
     *
     * @return string
     */
    protected function getNoteAssociationName($targetClass)
    {
        $noteTargetClass = empty($this->oldClassNames[$targetClass])
            ? $targetClass
            : $this->oldClassNames[$targetClass];

        return ExtendHelper::buildAssociationName($noteTargetClass);
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $sql
     * @param array           $params
     */
    protected function logQuery(LoggerInterface $logger, $sql, array $params = [])
    {
        $logger->debug(sprintf('Query: %s %s Parameters: %', $sql, PHP_EOL, print_r($params, true)));
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $classNamesMapping ['current class name' => 'old class name']
     */
    public function registerOldClassNames(array $classNamesMapping)
    {
        foreach ($classNamesMapping as $currentClassName => $oldClassName) {
            $this->registerOldClassNameForClass($currentClassName, $oldClassName);
        }
    }

    /**
     * @param string $class
     * @param string $oldClassName
     *
     * @return UpdateAssociationKindQuery
     */
    public function registerOldClassNameForClass($class, $oldClassName)
    {
        $this->oldClassNames[$class] = $oldClassName;

        return $this;
    }
}
