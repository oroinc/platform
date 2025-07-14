<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use PHPUnit\Framework\TestCase;

class CreateMigrationTableMigrationTest extends TestCase
{
    public function testUp(): void
    {
        $schema = new Schema();
        $queryBag = new QueryBag();
        $createMigration = new CreateMigrationTableMigration();
        $createMigration->up($schema, $queryBag);

        $this->assertEmpty($queryBag->getPreQueries());
        $this->assertEmpty($queryBag->getPostQueries());

        $table = $schema->getTable(CreateMigrationTableMigration::MIGRATION_TABLE);
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('bundle'));
        $this->assertTrue($table->hasColumn('version'));
        $this->assertTrue($table->hasColumn('loaded_at'));
    }
}
