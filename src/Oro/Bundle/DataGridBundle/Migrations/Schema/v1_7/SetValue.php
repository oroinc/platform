<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class SetValue implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 20;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_grid_view SET discr_type = :type',
                ['type' => 'grid_view'],
                ['type' => Types::STRING]
            )
        );
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_grid_view_user_rel SET type = :type',
                ['type' => 'grid_view_user'],
                ['type' => Types::STRING]
            )
        );
    }
}
