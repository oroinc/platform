<?php

namespace Oro\Bundle\ActivityBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;

/**
 * Provides an ability to create activity related associations.
 */
class ActivityExtension implements ExtendExtensionAwareInterface, NameGeneratorAwareInterface
{
    use ExtendExtensionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

    /**
     * Adds the association between the given table and the table contains activity records
     *
     * The activity entity must be included in 'activity' group ('groups' attribute of 'grouping' scope)
     *
     * @param Schema $schema
     * @param string $activityTableName Activity entity table name. It is owning side of the association
     * @param string $targetTableName   Target entity table name
     * @param bool   $immutable         Set TRUE to prohibit disabling the activity association from UI
     */
    public function addActivityAssociation(
        Schema $schema,
        $activityTableName,
        $targetTableName,
        $immutable = false
    ) {
        $targetTable = $schema->getTable($targetTableName);

        // Column names are used to show a title of target entity
        $targetTitleColumnNames = $targetTable->getPrimaryKey()->getColumns();
        // Column names are used to show detailed info about target entity
        $targetDetailedColumnNames = $targetTable->getPrimaryKey()->getColumns();
        // Column names are used to show target entity in a grid
        $targetGridColumnNames = $targetTable->getPrimaryKey()->getColumns();

        $activityClassName = $this->extendExtension->getEntityClassByTableName($activityTableName);

        $options = new OroOptions();
        $options->append(
            'activity',
            'activities',
            $activityClassName
        );
        if ($immutable) {
            $options->append(
                'activity',
                'immutable',
                $activityClassName
            );
        }

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
                    'without_default' => true
                ]
            ]
        );
    }

    /**
     * Gets a table name for many-to-many relation
     *
     * @param string $activityTableName Activity entity table name. It is owning side of the association.
     * @param string $targetTableName   Target entity table name.
     *
     * @return string
     */
    public function getAssociationTableName($activityTableName, $targetTableName)
    {
        $sourceClassName = $this->extendExtension->getEntityClassByTableName($activityTableName);
        $targetClassName = $this->extendExtension->getEntityClassByTableName($targetTableName);

        $associationName = ExtendHelper::buildAssociationName(
            $targetClassName,
            ActivityScope::ASSOCIATION_KIND
        );

        return $this->nameGenerator->generateManyToManyJoinTableName(
            $sourceClassName,
            $associationName,
            $targetClassName
        );
    }
}
