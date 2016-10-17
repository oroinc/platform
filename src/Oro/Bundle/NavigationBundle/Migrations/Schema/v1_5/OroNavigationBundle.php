<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;

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
        $this->updateOroNavigationMenuUpdateTable($schema);
    }

    /**
     * Update oro_navigation_menu_upd
     *
     * @param Schema $schema
     */
    protected function updateOroNavigationMenuUpdateTable(Schema $schema)
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        $table->changeColumn('ownership_type', ['type' => StringType::getType('string')]);
        $table->addColumn('is_divider', 'boolean', []);
        $table->addColumn('is_custom', 'boolean', []);
    }
}
