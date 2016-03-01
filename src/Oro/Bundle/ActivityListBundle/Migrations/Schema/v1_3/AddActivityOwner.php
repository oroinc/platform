<?php

namespace Oro\Bundle\ActivityListBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddActivityOwner implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addActivityOwner($schema);
    }

    /**
     * Adds Activity Owner
     *
     * @param Schema $schema
     */
    public static function addActivityOwner(Schema $schema)
    {
        /** Tables generation **/
        self::createOroActivityOwnerTable($schema);

        /** Foreign keys generation **/
        self::addOroActivityOwnerForeignKeys($schema);
    }

    /**
     * Create oro_activity_owner table
     *
     * @param Schema $schema
     */
    protected static function createOroActivityOwnerTable(Schema $schema)
    {
        $table = $schema->createTable('oro_activity_owner');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('activity_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['activity_id', 'user_id'], 'UNQ_activity_owner');
        $table->addIndex(['activity_id']);
        $table->addIndex(['organization_id']);
        $table->addIndex(['user_id'], 'idx_oro_activity_owner_user_id', []);
    }

    /**
     * Add oro_activity_owner foreign keys.
     *
     * @param Schema $schema
     */
    protected static function addOroActivityOwnerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_activity_owner');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_activity_list'),
            ['activity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
