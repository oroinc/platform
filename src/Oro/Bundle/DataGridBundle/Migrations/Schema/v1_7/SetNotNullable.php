<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetNotNullable implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_grid_view');
        $table->getColumn('discr_type')
            ->setType(Type::getType(Type::STRING))
            ->setOptions(['length' => 255, 'notnull' => true]);
        $table->addIndex(['discr_type'], 'idx_oro_grid_view_discr_type');

        $table = $schema->getTable('oro_grid_view_user_rel');
        $table->getColumn('type')
            ->setType(Type::getType(Type::STRING))
            ->setOptions(['length' => 255, 'notnull' => true]);
        $table->addIndex(['type'], 'idx_oro_grid_view_user_rel_type');
    }
}
