<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Schema\v1_3;

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
        self::addPKActiveDashboard($schema);
        self::addUserIndexActiveDashboard($schema);
    }

    /**
     * Adds PK to oro_dashboard_active table
     *
     * @param Schema   $schema
     */
    public static function addPKActiveDashboard(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_active');

        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Adds index, foreign key to oro_dashboard_active table
     *
     * @param Schema   $schema
     */
    public static function addUserIndexActiveDashboard(Schema $schema)
    {
        $table = $schema->getTable('oro_dashboard_active');
        $table->addIndex(['user_id'], 'IDX_dsh_active_usr_id', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
