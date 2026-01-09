<?php

namespace Oro\Bundle\ActivityListBundle\Migration\Extension;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendNameGeneratorAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;

/**
 * Provides an ability to create activity list related associations.
 */
class ActivityListExtension implements ExtendExtensionAwareInterface, NameGeneratorAwareInterface
{
    use ExtendExtensionAwareTrait;
    use ExtendNameGeneratorAwareTrait;

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
        $targetTitleColumnNames = $targetTable->getPrimaryKey()->getColumns();
        // Column names are used to show detailed info about target entity
        $targetDetailedColumnNames = $targetTable->getPrimaryKey()->getColumns();
        // Column names are used to show target entity in a grid
        $targetGridColumnNames = $targetTable->getPrimaryKey()->getColumns();

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

    /**
     * Add inheritance tables to target to show inherited activities
     *
     * @param Schema $schema
     * @param string $targetTableName Target entity table name
     * @param string $inheritanceTableName Inheritance entity table name
     * @param string[] $path Path of relations to target entity
     */
    public function addInheritanceTargets(
        Schema $schema,
        $targetTableName,
        $inheritanceTableName,
        $path
    ) {
        $targetTable = $schema->getTable($targetTableName);

        $options = new OroOptions();
        $inheritance['target'] = $this->extendExtension->getEntityClassByTableName($inheritanceTableName);
        $inheritance['path'] = $path;
        $inheritances[] = $inheritance;
        $options->append(
            'activity',
            'inheritance_targets',
            $inheritances
        );

        $targetTable->addOption(OroOptions::KEY, $options);
    }

    /**
     * Gets an activity list table name for many-to-many relation
     *
     * @param string $targetTableName Target entity table name.
     *
     * @return string
     */
    public function getAssociationTableName($targetTableName)
    {
        $targetClassName = $this->extendExtension->getEntityClassByTableName($targetTableName);

        $associationName = ExtendHelper::buildAssociationName(
            $targetClassName,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );

        return $this->nameGenerator->generateManyToManyJoinTableName(
            ActivityListEntityConfigDumperExtension::ENTITY_CLASS,
            $associationName,
            $targetClassName
        );
    }
}
