<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_6;

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
        /** Table updates **/
        $this->updateOroCommerceMenuUpdateTable($schema);
        $this->addOroCommerceMenuUpdateForeignKeys($schema);
    }

    /**
     * Update oro_navigation_menu_upd
     *
     * @param Schema $schema
     */
    protected function updateOroCommerceMenuUpdateTable(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        $table->dropIndex('oro_navigation_menu_upd_uidx');
        $table->dropColumn('ownership_type');
        $table->dropColumn('owner_id');
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['key', 'scope_id'], 'oro_navigation_menu_upd_uidx');
    }

    /**
     * Add oro_commerce_menu_upd foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCommerceMenuUpdateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
