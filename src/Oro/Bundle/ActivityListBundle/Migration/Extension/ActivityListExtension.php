<?php

namespace Oro\Bundle\ActivityListBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityListExtension implements ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Adds the association between the given table and the activity list table
     *
     * @param Schema $schema
     * @param string $targetTableName Target entity table name
     */
    public function addActivityListAssociation(
        Schema $schema,
        $targetTableName
    ) {
        $targetTable = $schema->getTable($targetTableName);

        // Column names are used to show a title of target entity
        $targetTitleColumnNames = $targetTable->getPrimaryKeyColumns();
        // Column names are used to show detailed info about target entity
        $targetDetailedColumnNames = $targetTable->getPrimaryKeyColumns();
        // Column names are used to show target entity in a grid
        $targetGridColumnNames = $targetTable->getPrimaryKeyColumns();

        $associationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTableName),
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );

        $this->extendExtension->addManyToManyRelation(
            $schema,
            $schema->getTable('oro_activity_list'),
            $associationName,
            $targetTable,
            $targetTitleColumnNames,
            $targetDetailedColumnNames,
            $targetGridColumnNames,
            [
                'extend' => [
                    'without_default' => true
                ]
            ]
        );
    }
}
