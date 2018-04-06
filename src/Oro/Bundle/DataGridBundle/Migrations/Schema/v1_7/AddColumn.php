<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddColumn implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_grid_view');
        $table->addColumn('discr_type', 'string', ['length' => 255, 'notnull' => false]);

        $table = $schema->getTable('oro_grid_view_user_rel');
        $table->addColumn('type', 'string', ['length' => 255, 'notnull' => false]);
    }
}
