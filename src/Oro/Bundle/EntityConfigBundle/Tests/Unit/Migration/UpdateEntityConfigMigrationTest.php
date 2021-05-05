<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEntityConfigMigrationTest extends \PHPUnit\Framework\TestCase
{
    public function testUp()
    {
        $commandExecutor = $this->createMock(CommandExecutor::class);
        $schema = $this->createMock(Schema::class);
        $queries = $this->createMock(QueryBag::class);

        $queries->expects($this->once())
            ->method('addQuery')
            ->with(new UpdateEntityConfigMigrationQuery($commandExecutor));

        $migration = new UpdateEntityConfigMigration($commandExecutor);
        $migration->up($schema, $queries);
    }
}
