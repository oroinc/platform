<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdatedColumnIndexMigration implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('index_table2');
        $table->getColumn('key')->setLength(1000);
        $table->addIndex(['key'], 'index2');
    }
}
