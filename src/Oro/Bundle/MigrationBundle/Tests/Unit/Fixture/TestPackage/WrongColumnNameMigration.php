<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class WrongColumnNameMigration implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('wrong_table');
        $table->addColumn('extra_long_column_bigger_30_chars', 'integer');
    }
}
