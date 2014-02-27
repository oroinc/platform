<?php

namespace Migration\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class Test2BundleMigration11 implements Migration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('test1table');
        $table->addColumn('another_column', 'int');

        return [];
    }
}
