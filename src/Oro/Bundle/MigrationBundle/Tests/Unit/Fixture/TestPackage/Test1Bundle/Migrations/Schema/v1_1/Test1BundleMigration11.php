<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class Test1BundleMigration11 implements Migration
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
