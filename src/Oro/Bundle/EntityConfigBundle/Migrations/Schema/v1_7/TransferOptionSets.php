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

class TransferOptionSets implements Migration, ContainerAwareInterface, OrderedMigrationInterface, DataStorageExtensionAwareInterface
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

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->metadataHelper = $container->get('oro_entity_extend.migration.entity_metadata_helper');
        $this->extendExtension = $container->get('oro_entity_extend.migration.extension.extend');
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $optionSets = $this->storage->get('existing_option_sets');

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

            $optionSet['table_name'] = $enumTableName;
            $updatedOptionSets[] = $optionSet;

            $this->extendExtension->addEnumField(
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

            //$table = $schema->getTable('oro_enum_' . $optionSet['table_name']);
            //$table->changeColumn('id', ['autoincrement' => true]);
            // TODO: add autoincrement for id column
        }


        $queries->addPostQuery(new ConvertOptionSetsToEnumsQuery(
            $this->metadataHelper,
            $updatedOptionSets
        ));

        // TODO: delete optionSet
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
