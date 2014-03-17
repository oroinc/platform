<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;

class UpdateEntityConfigMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $migration = new UpdateEntityConfigMigration(
            $commandExecutor
        );

        $schema = $this->getMockBuilder('Doctrine\DBAL\Schema\Schema')
            ->disableOriginalConstructor()
            ->getMock();

        $queries = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\QueryBag')
            ->disableOriginalConstructor()
            ->getMock();

        $queries->expects($this->at(0))
            ->method('addQuery')
            ->with(
                new UpdateEntityConfigMigrationQuery(
                    $commandExecutor
                )
            );

        $migration->up($schema, $queries);
    }
}
