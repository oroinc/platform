<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class OroDataGridBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_grid_view');

        $table->addColumn('columnsData', 'array', ['comment' => '(DC2Type:array)', 'notnull' => false]);

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_grid_view SET columnsData = :columnsData WHERE columnsData is NULL; ',
                ['columnsData' => serialize([])]
            )
        );
    }
}
