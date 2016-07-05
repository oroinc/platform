<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDataGridBundle implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 20;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_grid_view_user');
        $table->changeColumn('id', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('alias', 'string', ['length' => 255]);
        $table->addColumn('grid_name', 'string', ['length' => 255]);
        $table->changeColumn('grid_view_id', ['notnull' => false]);
        $table->changeColumn('user_id', ['notnull' => false]);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_grid_view'),
            ['grid_view_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_10ECBCA8BF53711B'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_10ECBCA8A76ED395'
        );
    }
}
