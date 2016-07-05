<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropPrimaryKey implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 10;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_grid_view_user');
        $table->removeForeignKey('FK_10ECBCA8BF53711B');
        $table->removeForeignKey('fk_oro_grid_view_user_user_id');
        $table->dropPrimaryKey();
    }
}
