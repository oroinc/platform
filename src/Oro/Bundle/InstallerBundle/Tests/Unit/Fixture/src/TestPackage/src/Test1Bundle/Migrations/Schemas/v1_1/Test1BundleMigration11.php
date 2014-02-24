<?php

namespace Migration\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class Test1BundleMigration11 implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('test1table');
        $table->addColumn('id', 'int');
        return [
            "ALTER TABLE TEST ADD COLUMN test_column INT NOT NULL",
        ];
    }
}
