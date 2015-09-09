<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DataStorageExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class CreateEnumsFromOptionSetsAndCleanUp implements
    Migration,
    ContainerAwareInterface,
    OrderedMigrationInterface,
    DataStorageExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    /** @var EntityMetadataHelper */
    protected $metadataHelper;

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var DataStorageExtension */
    protected $storage;

    public function setDataStorageExtension(DataStorageExtension $storage)
    {
        $this->storage  = $storage;
    }

    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        // no other way of doing this
        $this->metadataHelper = $container->get('oro_entity_extend.migration.entity_metadata_helper');
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $existingEnumTables = $this->storage->get('existing_enum_values');
        $optionSets         = $this->storage->get('existing_option_sets');
        $optionSetsValues   = $this->storage->get('existing_option_sets_values');
        $nameGenerator      = $this->extendExtension->getNameGenerator();
        $updatedOptionSets  = [];
        $optionSetMetadata  = [];

        foreach ($optionSets as $optionSet) {
            $data = $optionSet['data'];

            $entityTableName = $this->metadataHelper->getTableNameByEntityClass($optionSet['class_name']);

            // limit enum identifier to the size of 21
            $enumCode = sprintf(
                '%s_%s',
                substr($entityTableName, 0, 14),
                substr($optionSet['field_name'], 0, 6)
            );

            // check if there are already enums with that name and generate new one if so
            while (in_array(
                $nameGenerator->generateEnumTableName($enumCode),
                $existingEnumTables,
                true
            )
            ) {
                $enumCode = sprintf(
                    '%s_%s_%s',
                    substr($entityTableName, 0, 12),
                    substr($optionSet['field_name'], 0, 5),
                    substr(str_shuffle(MD5(microtime())), 0, 2)
                );
            }

            $table = $this->extendExtension->addEnumField(
                $schema,
                $entityTableName,
                $optionSet['field_name'],
                $enumCode,
                $data['extend']['set_expanded'],
                false,
                [
                    'extend' => ['owner' => $data['extend']['owner']]
                ]
            );

            $enumClassName = ExtendHelper::buildEnumValueClassName($enumCode);
            $tableName = $nameGenerator->generateManyToManyJoinTableName($optionSet['class_name'], $optionSet['field_name'], $enumClassName);

            $optionSetMetadata[$optionSet['id']] = [
                'is_multiple' => (bool) $data['extend']['set_expanded'],
                'join_table_name' => $tableName,
                'self_pk' => $nameGenerator->generateManyToManyRelationColumnName($optionSet['class_name']),
                'target_pk' => $nameGenerator->generateManyToManyRelationColumnName($enumClassName)
            ];

            $optionSet['table_name'] = $table->getName();
            $existingEnumTables[]    = $table->getName();
            $updatedOptionSets[]     = $optionSet;
        }

        $queries->addPostQuery(new PopulateNewEnumsWithValuesQuery(
            $this->metadataHelper,
            $updatedOptionSets
        ));

        $this->fillNewEnumFieldsWithData($queries, $optionSetsValues, $optionSetMetadata);

        $queries->addPostQuery('DROP TABLE IF EXISTS oro_entity_config_optset_rel');
        $queries->addPostQuery('DROP TABLE IF EXISTS oro_entity_config_optionset');
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    protected function fillNewEnumFieldsWithData(QueryBag $queries, $optionSetsValues, $optionSetMetadata)
    {
        $nameGenerator = $this->extendExtension->getNameGenerator();

        foreach ($optionSetsValues as $value) {
            $isMultiple = $optionSetMetadata[$value['field_id']]['is_multiple'];
            $tableName  = $optionSetMetadata[$value['field_id']]['join_table_name'];

            $fkFieldName = $isMultiple
                    ? $nameGenerator->generateMultiEnumSnapshotColumnName($value['field_name'])
                    : $nameGenerator->generateManyToOneRelationColumnName($value['field_name']);

            $query = sprintf(
                'UPDATE %s SET %s = ? WHERE id = ?',
                $this->metadataHelper->getTableNameByEntityClass($value['class_name']),
                $fkFieldName
            );

            $queries->addPostQuery(new ParametrizedSqlMigrationQuery($query, [$value['labels'], $value['row_id']]));

            if ($isMultiple) {
                $query = sprintf(
                    'INSERT INTO %s (%s, %s) VALUES (?, ?)',
                    $tableName,
                    $optionSetMetadata[$value['field_id']]['self_pk'],
                    $optionSetMetadata[$value['field_id']]['target_pk']
                );

                foreach (explode(',', $value['labels']) as $label) {
                    $queries->addPostQuery(new ParametrizedSqlMigrationQuery($query, [$value['row_id'], $label]));
                }
            }
        }
    }
}
