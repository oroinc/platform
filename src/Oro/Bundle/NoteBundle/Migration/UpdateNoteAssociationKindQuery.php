<?php

namespace Oro\Bundle\NoteBundle\Migration;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateNoteAssociationKindQuery extends ParametrizedMigrationQuery
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
        $logger->info(
            sprintf(
                'Update activity association kind for entity %s.',
                Note::class
            )
        );
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

            $this->removeOldNoteAssociation($logger, $targetEntityClassName, $noteEntityConfig, $dryRun);

            unset($entityConfigurationRow['data']['note']['enabled']);
            $this->saveEntityConfig($logger, $entityConfigurationRow, $dryRun);
        }

        $this->saveEntityConfig($logger, $noteEntityConfig, $dryRun);
        $this->applyActivityAssociationSchemaChanges($logger, $fromSchema, $dryRun);

        foreach ($entitiesForDataMigration as $targetEntityClassName => $targetTableName) {
            $this->migrateNoteRelationDataToActivityRelationKind(
                $logger,
                $targetTableName,
                $targetEntityClassName,
                $dryRun
            );
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
        $this->logQuery($logger, $sql);

        $entityConfigs = $this->connection->fetchAll($sql);

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
        $sql = 'SELECT id, class_name, data FROM oro_entity_config WHERE class_name = ?';
        $parameters = ['Oro\Bundle\NoteBundle\Entity\Note'];

        $this->logQuery($logger, $sql, $parameters);

        $noteEntityConfig = $this->connection->fetchAssoc($sql, $parameters);
        $noteEntityConfig['data'] = $this->connection->convertToPHPValue($noteEntityConfig['data'], Type::TARRAY);

        return $noteEntityConfig;
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

        $sql = 'DELETE FROM oro_entity_config_field WHERE field_name = ? AND entity_id= ?';
        $parameters = [$noteAssociationName, $noteEntityConfig['id']];

        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $parameters);
        }

        unset($noteEntityConfig['data']['extend']['schema']['relation'][$noteAssociationName]);

        $relationKeyName = ExtendHelper::buildRelationKey(
            'Oro\Bundle\NoteBundle\Entity\Note',
            $noteAssociationName,
            'manyToOne',
            $targetEntityClassName
        );
        unset($noteEntityConfig['data']['extend']['relation'][$relationKeyName]);
    }

    /**
     * @param LoggerInterface $logger
     * @param array           $entityConfig
     * @param boolean         $dryRun
     */
    protected function saveEntityConfig(LoggerInterface $logger, array $entityConfig, $dryRun)
    {
        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [
            $this->connection->convertToDatabaseValue($entityConfig['data'], Type::TARRAY),
            $entityConfig['id']
        ];

        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $parameters);
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
    protected function migrateNoteRelationDataToActivityRelationKind(
        LoggerInterface $logger,
        $targetTable,
        $targetClass,
        $dryRun
    ) {
        $associationTableName = $this->activityExtension->getAssociationTableName('oro_note', $targetTable);

        $associationColumnName = $this->nameGenerator->generateManyToManyJoinTableColumnName($targetClass);

        $noteAssociationColumnName = $this->getNoteAssociationColumnName($targetClass);
        $sql = <<<SQL
          INSERT INTO $associationTableName (note_id, $associationColumnName)
          SELECT id, $noteAssociationColumnName
          FROM oro_note WHERE $noteAssociationColumnName IS NOT NULL
SQL;

        $this->logQuery($logger, $sql);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql);
        }

        $schemaManager = $this->connection->getSchemaManager();
        foreach ($schemaManager->listTableForeignKeys('oro_note') as $foreignKey) {
            if (in_array($noteAssociationColumnName, $foreignKey->getColumns())) {
                $schemaManager->dropForeignKey($foreignKey, 'oro_note');
            }
        }

        $sql = "ALTER TABLE oro_note DROP COLUMN {$noteAssociationColumnName}";
        $this->logQuery($logger, $sql);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql);
        }
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
     */
    public function registerOldClassNameForClass($class, $oldClassName)
    {
        $this->oldClassNames[$class] = $oldClassName;
    }
}
