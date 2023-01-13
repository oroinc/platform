<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds is_synthetic field to MenuUpdate.
 */
class AddSyntheticFieldToMenuUpdate implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_navigation_menu_upd');
        if (!$table->hasColumn('is_synthetic')) {
            $table->addColumn('is_synthetic', 'boolean', ['notnull' => true, 'default' => false]);
        }
    }
}
