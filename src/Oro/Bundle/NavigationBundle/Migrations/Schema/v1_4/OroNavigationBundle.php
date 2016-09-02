<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // @codingStandardsIgnoreStart

        /** Generate table oro_navigation_menu_update **/
        $table = $schema->createTable('oro_navigation_menu_update');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_id', 'string', ['length' => 100]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('ownership_type', 'integer');
        $table->addColumn('owner_id', 'integer', []);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('priority', 'integer', []);
        $table->setPrimaryKey(['id']);

        // @codingStandardsIgnoreEnd
    }
}
