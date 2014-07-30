<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOrganizationFields($schema);
        self::oroUserOrganizationTable($schema);
        self::oroUserOrganizationForeignKeys($schema);
    }

    /**
     * Generate table oro_user_organization
     *
     * @param Schema $schema
     */
    public static function oroUserOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable('oro_user_organization');

        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('organization_id', 'integer', []);

        $table->setPrimaryKey(['user_id', 'organization_id']);

        $table->addIndex(['user_id'], 'IDX_A9BB6519A76ED395', []);
        $table->addIndex(['organization_id'], 'IDX_A9BB651932C8A3DE', []);
    }

    /**
     * Generate foreign keys for table oro_user_organization
     *
     * @param Schema $schema
     */
    public static function oroUserOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_user_organization');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Adds organization_id field
     *
     * @param Schema $schema
     */
    public static function addOrganizationFields(Schema $schema)
    {
        $table = $schema->getTable('oro_access_group');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_FEF9EDB732C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_access_role');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_673F65E732C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_user');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addIndex(['organization_id'], 'IDX_F82840BC32C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
