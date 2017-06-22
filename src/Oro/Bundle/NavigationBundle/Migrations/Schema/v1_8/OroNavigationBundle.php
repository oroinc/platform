<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroMenuUserAgentConditionTable($schema);
        $this->addOroMenuUserAgentConditionForeignKeys($schema);
    }

    /**
     * Create `oro_menu_user_agent_condition` table
     *
     * @param Schema $schema
     */
    protected function createOroMenuUserAgentConditionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_menu_user_agent_condition');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('condition_group_identifier', 'integer', []);
        $table->addColumn('operation', 'string', ['length' => 255]);
        $table->addColumn('value', 'string', ['length' => 255]);
        $table->addColumn('menu_update_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }
    
    /**
     * Add `oro_menu_user_agent_condition` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroMenuUserAgentConditionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_menu_user_agent_condition');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_navigation_menu_upd'),
            ['menu_update_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
