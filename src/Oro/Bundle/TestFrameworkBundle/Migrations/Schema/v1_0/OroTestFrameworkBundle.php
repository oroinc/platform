<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration, ActivityExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addTestActivityTable($schema);
        self::addTestActivityTargetTable($schema);
        self::addOrganizationFields($schema);
        self::addOwnerFields($schema);
        self::addActivityAssociations($schema, $this->activityExtension);
    }

    /**
     * @param Schema $schema
     */
    public static function addTestActivityTable(Schema $schema)
    {
        $table = $schema->createTable('test_activity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('message', 'text');
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    public static function addTestActivityTargetTable(Schema $schema)
    {
        $table = $schema->createTable('test_activity_target');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    public static function addOrganizationFields(Schema $schema)
    {
        $table = $schema->getTable('test_activity');

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_3917020C32C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    public static function addOwnerFields(Schema $schema)
    {
        $table = $schema->getTable('test_activity');

        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    public static function addActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $activityExtension->addActivityAssociation($schema, 'test_activity', 'test_activity_target', true);
    }
}
