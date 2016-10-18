<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Oro\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;

class SqlSchemaUpdateMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testIsUpdateRequired()
    {
        $query = new SqlSchemaUpdateMigrationQuery('ALTER TABLE');

        $this->assertInstanceOf('Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery', $query);
        $this->assertInstanceOf('Oro\Bundle\MigrationBundle\Migration\SchemaUpdateQuery', $query);
        $this->assertTrue($query->isUpdateRequired());
    }
}
