<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;

class CreateMigrationTableMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $schema = new Schema();
        $createMigration = new CreateMigrationTableMigration();
        $this->assertEmpty($createMigration->up(($schema)));
        $table = $schema->getTable(CreateMigrationTableMigration::MIGRATION_TABLE);
        $this->assertTrue($table->hasColumn('id'));
        $this->assertTrue($table->hasColumn('bundle'));
        $this->assertTrue($table->hasColumn('version'));
        $this->assertTrue($table->hasColumn('loaded_at'));
    }
}
