<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_1;

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
        // added field "is_default"
        $table = $schema->getTable('oro_dashboard');
        $table->addColumn('is_default', 'boolean', ['default' => '0']);
        $table->addIndex(['is_default'], 'dashboard_is_default_idx');

        // added fields "createdAt" and "updatedAt"
        $table->addColumn('createdAt', 'datetime');
        $table->addColumn('updatedAt', 'datetime', ['notnull' => false]);

        /** Generate table oro_dashboard_user_widget **/
        $table = $schema->createTable('oro_dashboard_user_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('widget_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_expanded', 'boolean', []);
        $table->addColumn('layout_position', 'simple_array', ['comment' => '(DC2Type:simple_array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['widget_id'], 'IDX_4B4F5F87FBE885E2', []);
        $table->addIndex(['user_owner_id'], 'IDX_4B4F5F879EB185F9', []);
        /** End of generate table oro_dashboard_user_widget **/
    }
}
