<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use PHPUnit\Framework\TestCase;

class UpdateEntityConfigMigrationTest extends TestCase
{
    public function testUp(): void
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
