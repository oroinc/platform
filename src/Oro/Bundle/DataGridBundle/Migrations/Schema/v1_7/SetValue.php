<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetValue implements Migration, OrderedMigrationInterface
{
    /** {@inheritdoc} */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_grid_view SET discr_type = :type',
                ['type' => 'grid_view'],
                ['type' => Type::STRING]
            )
        );
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_grid_view_user_rel SET type = :type',
                ['type' => 'grid_view_user'],
                ['type' => Type::STRING]
            )
        );
    }
}
