<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDashboardBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Generate table oro_dashboard **/
        $table = $schema->createTable('oro_dashboard');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'IDX_DF2802EF9EB185F9', []);
        /** End of generate table oro_dashboard **/

        /** Generate foreign keys for table oro_dashboard **/
        $table = $schema->getTable('oro_dashboard');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_dashboard **/

        /** Generate table oro_dashboard_widget **/
        $table = $schema->createTable('oro_dashboard_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('layout_position', 'simple_array', []);
        $table->addColumn('is_expanded', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['dashboard_id'], 'IDX_4B6C43ACB9D04D2B', []);
        /** End of generate table oro_dashboard_widget **/

        /** Generate foreign keys for table oro_dashboard_widget **/
        $table = $schema->getTable('oro_dashboard_widget');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_dashboard_widget **/

        /** Generate table oro_dashboard_active **/
        $table = $schema->createTable('oro_dashboard_active');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('dashboard_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['user_id']);
        /** End of generate table oro_dashboard_active **/

        /** Generate foreign keys for table oro_dashboard_active **/
        $table = $schema->getTable('oro_dashboard_active');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dashboard'),
            ['dashboard_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_dashboard_active **/
    }
}
