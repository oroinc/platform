<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityExtension implements ExtendExtensionAwareInterface
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
     * Adds the association between the given table and the table contains activity records
     *
     * The activity entity must be included in 'activity' group ('groups' attribute of 'grouping' scope)
     *
     * @param Schema   $schema
     * @param string   $activityTableName         Activity entity table name. It is owning side of the association
     * @param string   $targetTableName           Target entity table name
     * @param string[] $targetTitleColumnNames    Column names are used to show a title of target entity
     * @param string[] $targetDetailedColumnNames Column names are used to show detailed info about target entity
     * @param string[] $targetGridColumnNames     Column names are used to show target entity in a grid
     */
    public function addActivityAssociation(
        Schema $schema,
        $activityTableName,
        $targetTableName,
        $targetTitleColumnNames = null,
        $targetDetailedColumnNames = null,
        $targetGridColumnNames = null
    ) {
        $targetTable = $schema->getTable($targetTableName);

        if (empty($targetTitleColumnNames)) {
            $targetTitleColumnNames = $targetTable->getPrimaryKeyColumns();
        }
        if (empty($targetDetailedColumnNames)) {
            $targetDetailedColumnNames = $targetTable->getPrimaryKeyColumns();
        }
        if (empty($targetGridColumnNames)) {
            $targetGridColumnNames = $targetTable->getPrimaryKeyColumns();
        }

        $options = new OroOptions();
        $options->append(
            'activity',
            'activities',
            $this->extendExtension->getEntityClassByTableName($activityTableName)
        );
        $targetTable->addOption(OroOptions::KEY, $options);

        $associationName = ExtendHelper::buildAssociationName(
            $this->extendExtension->getEntityClassByTableName($targetTableName),
            ActivityScope::ASSOCIATION_KIND
        );

        $this->extendExtension->addManyToManyRelation(
            $schema,
            $activityTableName,
            $associationName,
            $targetTable,
            $targetTitleColumnNames,
            $targetDetailedColumnNames,
            $targetGridColumnNames,
            [
                'extend' => [
                    'owner'           => ExtendScope::OWNER_SYSTEM,
                    'is_extend'       => true,
                    'without_default' => true
                ]
            ]
        );
    }
}
