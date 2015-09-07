<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

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
        $nameGenerator      = $this->extendExtension->getNameGenerator();

        $updatedOptionSets = [];
        foreach ($optionSets as $optionSet) {
            $data = $optionSet['data'];

            $entityTableName = $this->metadataHelper->getTableNameByEntityClass($optionSet['class_name']);

            // limit enum identifier to the size of 21
            $enumTableName = sprintf(
                '%s_%s',
                substr($entityTableName, 0, 14),
                substr($optionSet['field_name'], 0, 6)
            );

            // check if there are already enums with that name and generate new one if so
            while (in_array(
                $nameGenerator->generateEnumTableName($enumTableName),
                $existingEnumTables,
                true
            )
            ) {
                $enumTableName = sprintf(
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
                $enumTableName,
                $data['extend']['set_expanded'],
                false,
                [
                    'extend' => ['owner' => $data['extend']['owner']]
                ]
            );

            $optionSet['table_name'] = $table->getName();
            $existingEnumTables[]    = $table->getName();
            $updatedOptionSets[]     = $optionSet;
        }

        $queries->addPostQuery(new PopulateNewEnumsWithValuesQuery(
            $this->metadataHelper,
            $updatedOptionSets
        ));
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
}
