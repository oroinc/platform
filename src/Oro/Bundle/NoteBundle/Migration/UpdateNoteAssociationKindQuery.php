<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateNoteAssociationKindQuery extends ParametrizedMigrationQuery
{
    const NOTE_CLASS = 'Oro\Bundle\NoteBundle\Entity\Note';
    const NOTE_TABLE = 'oro_note';

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
        $logger = new ArrayLogger();
        $logger->info('Update note associations.');
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param boolean $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $fromSchema = clone $this->schema;

        $noteEntityConfig = $this->getNoteEntityConfig($logger);

        $entitiesForDataMigration = [];
        $noteTable = $this->schema->getTable(self::NOTE_TABLE);
        $entityConfigs = $this->getApplicableEntityConfigs($logger);
        foreach ($entityConfigs as $entityConfigurationRow) {
            $targetEntityClassName = $entityConfigurationRow['class_name'];
            $targetTableName = $this->extendExtension->getTableNameByEntityClass($targetEntityClassName);
            $noteAssociationColumnName = $this->getNoteAssociationColumnName($targetEntityClassName);
            if (!$targetTableName
                || !$this->schema->hasTable($targetTableName)
                || !$noteTable->hasColumn($noteAssociationColumnName)
            ) {
                continue;
            }
            $entitiesForDataMigration[$targetEntityClassName] = $targetTableName;

            $associationTableName = $this->getNoteActivityAssociationTableName($targetTableName);
            if (!$this->schema->hasTable($associationTableName)) {
                $this->activityExtension->addActivityAssociation($this->schema, self::NOTE_TABLE, $targetTableName);
            }

            $this->removeOldNoteAssociation($logger, $targetEntityClassName, $noteEntityConfig, $dryRun);

            unset($entityConfigurationRow['data']['note']['enabled']);
            $this->saveEntityConfig($logger, $entityConfigurationRow, $dryRun);
        }

        $this->saveEntityConfig($logger, $noteEntityConfig, $dryRun);
        $this->applyActivityAssociationSchemaChanges($logger, $fromSchema, $dryRun);

        foreach ($entitiesForDataMigration as $targetEntityClassName => $targetTableName) {
            $this->migrateNoteData($logger, $targetTableName, $targetEntityClassName, $dryRun);
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array [['id' => entity config id, 'class_name' => entity class, 'data' => entity config], ...]
     */
    protected function getApplicableEntityConfigs(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';

        $this->logQuery($logger, $sql);
        $entityConfigs = $this->connection->fetchAll($sql);

        $result = [];
        foreach ($entityConfigs as $entityConfig) {
            $entityConfig['data'] = $this->connection->convertToPHPValue($entityConfig['data'], 'array');
            if (!empty($entityConfig['data']['note']['enabled'])) {
                $result[] = $entityConfig;
            }
        }

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array ['id' => entity config id, 'data' => entity config]
     */
    protected function getNoteEntityConfig(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = :class LIMIT 1';
        $params = ['class' => self::NOTE_CLASS];
        $types = ['class' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);
        $result = $this->connection->fetchAssoc($sql, $params, $types);
        $result['data'] = $this->connection->convertToPHPValue($result['data'], 'array');

        return $result;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $targetEntityClassName
     * @param array           $noteEntityConfig
     * @param boolean         $dryRun
     */
    protected function removeOldNoteAssociation(
        LoggerInterface $logger,
        $targetEntityClassName,
        array &$noteEntityConfig,
        $dryRun
    ) {
        $noteAssociationName = $this->getNoteAssociationName($targetEntityClassName);

        $sql = 'DELETE FROM oro_entity_config_field WHERE field_name = :fieldName AND entity_id = :entityId';
        $params = ['fieldName' => $noteAssociationName, 'entityId' => $noteEntityConfig['id']];
        $types = ['fieldName' => 'string', 'entityId' => 'integer'];

        $this->logQuery($logger, $sql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $params, $types);
        }

        unset($noteEntityConfig['data']['extend']['schema']['relation'][$noteAssociationName]);

        $relationKey = ExtendHelper::buildRelationKey(
            self::NOTE_CLASS,
            $noteAssociationName,
            'manyToOne',
            $targetEntityClassName
        );
        unset($noteEntityConfig['data']['extend']['relation'][$relationKey]);
    }

    /**
     * @param LoggerInterface $logger
     * @param array           $entityConfig
     * @param boolean         $dryRun
     */
    protected function saveEntityConfig(LoggerInterface $logger, array $entityConfig, $dryRun)
    {
        $sql = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $params = ['data' => $entityConfig['data'], 'id' => $entityConfig['id']];
        $types = ['data' => 'array', 'id' => 'integer'];

        $this->logQuery($logger, $sql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param Schema          $fromSchema
     * @param boolean         $dryRun
     */
    protected function applyActivityAssociationSchemaChanges(LoggerInterface $logger, Schema $fromSchema, $dryRun)
    {
        $comparator = new Comparator();
        $platform = $this->connection->getDatabasePlatform();
        $schemaDiff = $comparator->compare($fromSchema, $this->schema);
        $queries = $schemaDiff->toSql($platform);
        foreach ($queries as $sql) {
            $this->logQuery($logger, $sql);
            if (!$dryRun) {
                $this->connection->executeUpdate($sql);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param string $targetTable
     * @param string $targetClass
     * @param boolean $dryRun
     */
    protected function migrateNoteData(LoggerInterface $logger, $targetTable, $targetClass, $dryRun)
    {
        $associationTableName = $this->getNoteActivityAssociationTableName($targetTable);
        $associationColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName($targetClass);
        $noteAssociationColumnName = $this->getNoteAssociationColumnName($targetClass);
        $sql = sprintf(
            'INSERT INTO %1$s (note_id, %2$s) SELECT id, %3$s FROM %4$s WHERE %3$s IS NOT NULL',
            $associationTableName,
            $associationColumnName,
            $noteAssociationColumnName,
            self::NOTE_TABLE
        );

        $this->logQuery($logger, $sql);
        if (!$dryRun) {
            $this->connection->executeUpdate($sql);
        }

        $schemaManager = $this->connection->getSchemaManager();
        foreach ($schemaManager->listTableForeignKeys(self::NOTE_TABLE) as $foreignKey) {
            if (in_array($noteAssociationColumnName, $foreignKey->getColumns(), true)) {
                $schemaManager->dropForeignKey($foreignKey, self::NOTE_TABLE);
            }
        }

        $sql = sprintf('ALTER TABLE %s DROP COLUMN %s', self::NOTE_TABLE, $noteAssociationColumnName);
        $this->logQuery($logger, $sql);
        if (!$dryRun) {
            $this->connection->executeUpdate($sql);
        }
    }

    /**
     * @param string $targetTableName
     *
     * @return string
     */
    protected function getNoteActivityAssociationTableName($targetTableName)
    {
        return $this->activityExtension->getAssociationTableName(self::NOTE_TABLE, $targetTableName);
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
     * @param array $classNamesMapping [current class name => old class name, ...]
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
     */
    public function registerOldClassNameForClass($class, $oldClassName)
    {
        $this->oldClassNames[$class] = $oldClassName;
    }
}
