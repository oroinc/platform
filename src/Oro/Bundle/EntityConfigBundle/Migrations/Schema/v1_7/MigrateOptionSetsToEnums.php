<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateOptionSetsToEnums implements
    Migration,
    OrderedMigrationInterface,
    DataStorageExtensionAwareInterface,
    OutdatedExtendExtensionAwareInterface
{
    use DataStorageExtensionAwareTrait;
    use OutdatedExtendExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $existingEnumTables = $this->getExistingEnumTables($schema);

        $optionSets = $this->dataStorageExtension->get('existing_option_sets');
        foreach ($optionSets as &$optionSet) {
            $entityTableName = $this->outdatedExtendExtension->getTableNameByEntityClass($optionSet['class_name']);
            $data = $optionSet['data'];

            $enumCode = $this->buildEnumCode(
                $optionSet['class_name'],
                $optionSet['field_name'],
                $existingEnumTables
            );
            $enumTable = $this->outdatedExtendExtension->addOutdatedEnumField(
                $schema,
                $entityTableName,
                $optionSet['field_name'],
                $enumCode,
                $data['extend']['set_expanded'],
                false,
                [
                    'extend' => [
                        'owner' => $data['extend']['owner']
                    ]
                ]
            );
            $existingEnumTables[] = $enumTable->getName();

            $optionSet['table_name'] = $entityTableName;
            if ($data['extend']['set_expanded']) {
                $pkColumns = $schema->getTable($entityTableName)
                    ->getPrimaryKey()
                    ->getColumns();

                $optionSet['pk_column_name'] = reset($pkColumns);
            }
            $optionSet['enum_class_name'] = OutdatedExtendExtension::buildEnumValueClassName($enumCode);
            $optionSet['enum_table_name'] = $enumTable->getName();
        }

        $this->fillEnumValues($queries, $optionSets);
        $this->assignEnumValues($queries, $optionSets);
        $this->removeOptionSetAttributes($queries, $optionSets);
    }

    /**
     * @param Schema $schema
     *
     * @return string[]
     */
    protected function getExistingEnumTables(Schema $schema)
    {
        $result = [];
        foreach ($schema->getTables() as $table) {
            $tableName = $table->getName();
            if (strrpos($tableName, 'oro_enum') === 0 && $tableName !== 'oro_enum_value_trans') {
                $result[] = $tableName;
            }
        }

        return $result;
    }

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @param string[] $existingEnumTables
     *
     * @return string
     */
    protected function buildEnumCode($entityClassName, $fieldName, $existingEnumTables)
    {
        $enumCode = ExtendHelper::generateEnumCode(
            $entityClassName,
            $fieldName,
            $this->outdatedExtendExtension->getNameGenerator()->getMaxEnumCodeSize()
        );

        // check if an enum with that code is already exist and generate new code if so
        while (in_array($this->outdatedExtendExtension::generateEnumTableName($enumCode), $existingEnumTables, true)) {
            $enumCode = sprintf(
                'enum_%s_%s',
                dechex(crc32(ExtendHelper::getShortClassName($entityClassName))),
                dechex(crc32(microtime()))
            );
        }

        return $enumCode;
    }

    protected function fillEnumValues(QueryBag $queries, array &$optionSets)
    {
        foreach ($optionSets as &$optionSet) {
            $query = sprintf(
                'INSERT INTO %s (id, name, priority, is_default) '
                . 'VALUES (:id, :name, :priority, :is_default)',
                $optionSet['enum_table_name']
            );

            foreach ($optionSet['values'] as &$option) {
                $option['enum_value_id'] = ExtendHelper::buildEnumInternalId($option['label']);
                $params = [
                    'id' => $option['enum_value_id'],
                    'name' => $option['label'],
                    'priority' => $option['priority'],
                    'is_default' => $option['is_default']
                ];
                $types = [
                    'id' => 'string',
                    'name' => 'string',
                    'priority' => 'integer',
                    'is_default' => 'boolean'
                ];

                $queries->addPostQuery(
                    new ParametrizedSqlMigrationQuery($query, $params, $types)
                );
            }
        }
    }

    protected function assignEnumValues(QueryBag $queries, array $optionSets)
    {
        foreach ($optionSets as $optionSet) {
            $snapshots = [];
            foreach ($optionSet['values'] as $option) {
                if (empty($option['assignments'])) {
                    continue;
                }

                foreach ($option['assignments'] as $entityId) {
                    if ($optionSet['data']['extend']['set_expanded']) {
                        $this->assignMultiEnumValue(
                            $queries,
                            $optionSet['table_name'],
                            $optionSet['pk_column_name'],
                            $optionSet['class_name'],
                            $optionSet['field_name'],
                            $optionSet['enum_class_name'],
                            $entityId,
                            $option['enum_value_id']
                        );
                        $snapshots[$entityId][] = $option['enum_value_id'];
                    } else {
                        $this->assignEnumValue(
                            $queries,
                            $optionSet['table_name'],
                            $optionSet['field_name'],
                            $entityId,
                            $option['enum_value_id']
                        );
                    }
                }
            }
            if (!empty($snapshots)) {
                foreach ($snapshots as $entityId => $snapshot) {
                    $this->updateEnumSnapshot(
                        $queries,
                        $optionSet['table_name'],
                        $optionSet['field_name'],
                        $entityId,
                        $snapshot
                    );
                }
            }
        }
    }

    /**
     * @param QueryBag $queries
     * @param string $entityTableName
     * @param string $entityFieldName
     * @param int $entityId
     * @param string $enumValueId
     */
    protected function assignEnumValue(
        QueryBag $queries,
        $entityTableName,
        $entityFieldName,
        $entityId,
        $enumValueId
    ) {
        $nameGenerator = $this->outdatedExtendExtension->getNameGenerator();

        $query = sprintf(
            'UPDATE %s SET %s = :enumValueId WHERE id = :entityId',
            $entityTableName,
            $nameGenerator->generateRelationColumnName($entityFieldName)
        );
        $params = [
            'entityId' => $entityId,
            'enumValueId' => $enumValueId
        ];
        $types = [
            'entityId' => 'integer',
            'enumValueId' => 'string'
        ];

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery($query, $params, $types)
        );
    }

    /**
     * @param QueryBag $queries
     * @param string $entityTableName
     * @param string $entityPkColumnName
     * @param string $entityClassName
     * @param string $entityFieldName
     * @param string $enumClassName
     * @param int $entityId
     * @param string $enumValueId
     */
    protected function assignMultiEnumValue(
        QueryBag $queries,
        $entityTableName,
        $entityPkColumnName,
        $entityClassName,
        $entityFieldName,
        $enumClassName,
        $entityId,
        $enumValueId
    ) {
        $nameGenerator = $this->outdatedExtendExtension->getNameGenerator();

        $query = sprintf(
            'INSERT INTO %s (%s, %s) SELECT %s, :enumValueId '
            . 'FROM %s WHERE %s = :entityId',
            $nameGenerator->generateManyToManyJoinTableName($entityClassName, $entityFieldName, $enumClassName),
            $nameGenerator->generateManyToManyJoinTableColumnName($entityClassName),
            $nameGenerator->generateManyToManyJoinTableColumnName($enumClassName),
            $entityPkColumnName,
            $entityTableName,
            $entityPkColumnName
        );
        $params = [
            'entityId' => $entityId,
            'enumValueId' => $enumValueId
        ];
        $types = [
            'entityId' => 'integer',
            'enumValueId' => 'string'
        ];

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery($query, $params, $types)
        );
    }

    /**
     * @param QueryBag $queries
     * @param string $entityTableName
     * @param string $entityFieldName
     * @param int $entityId
     * @param string[] $enumValueIds
     */
    protected function updateEnumSnapshot(
        QueryBag $queries,
        $entityTableName,
        $entityFieldName,
        $entityId,
        $enumValueIds
    ) {
        $nameGenerator = $this->outdatedExtendExtension->getNameGenerator();

        $query = sprintf(
            'UPDATE %s SET %s = :snapshot WHERE id = :entityId',
            $entityTableName,
            $nameGenerator->generateMultiEnumSnapshotColumnName($entityFieldName)
        );
        $params = [
            'entityId' => $entityId,
            'snapshot' => $this->buildSnapshotValue($enumValueIds)
        ];
        $types = [
            'entityId' => 'integer',
            'enumValueId' => 'string'
        ];

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery($query, $params, $types)
        );
    }

    /**
     * @param string[] $ids
     *
     * @return string
     */
    protected function buildSnapshotValue(array $ids)
    {
        sort($ids);
        $snapshot = implode(',', $ids);

        if (strlen($snapshot) > ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH) {
            $snapshot = substr($snapshot, 0, ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH);
            $lastDelim = strrpos($snapshot, ',');
            if (ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH - $lastDelim - 1 < 3) {
                $lastDelim = strrpos($snapshot, ',', -(strlen($snapshot) - $lastDelim + 1));
            }
            $snapshot = substr($snapshot, 0, $lastDelim + 1) . '...';
        }

        return $snapshot;
    }

    protected function removeOptionSetAttributes(QueryBag $queries, array $optionSets)
    {
        $configFieldIds = array_map(
            function (array $optionSet) {
                return $optionSet['field_id'];
            },
            $optionSets
        );
        $queries->addPostQuery(new RemoveOptionSetAttributesQuery($configFieldIds));
    }
}
