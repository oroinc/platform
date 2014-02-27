<?php

namespace Migration\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class Test1BundleMigration10 implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "CREATE TABLE TEST (id INT AUTO_INCREMENT NOT NULL)",
        ];
    }
}
