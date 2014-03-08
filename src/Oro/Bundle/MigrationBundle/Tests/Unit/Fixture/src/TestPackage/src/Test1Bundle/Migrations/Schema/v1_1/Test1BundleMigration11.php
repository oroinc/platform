<?php

namespace Migration\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class Test1BundleMigration11 extends Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('test1table');
        $table->addColumn('id', 'integer');

        $queries->addQuery('ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL');
    }
}
