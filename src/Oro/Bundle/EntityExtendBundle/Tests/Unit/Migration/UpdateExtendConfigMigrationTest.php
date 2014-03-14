<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;

class UpdateExtendConfigMigrationTest extends \PHPUnit_Framework_TestCase
{
    public function testUp()
    {
        $extendOptions = ['test'];

        $optionsPath = realpath(__DIR__ . '/../Fixtures') . '/test_options.yml';
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $migration = new UpdateExtendConfigMigration(
            $commandExecutor,
            $optionsPath
        );

        $schema = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendSchema')
            ->disableOriginalConstructor()
            ->getMock();
        $queries = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Migration\QueryBag')
            ->disableOriginalConstructor()
            ->getMock();

        $schema->expects($this->once())
            ->method('getExtendOptions')
            ->will($this->returnValue($extendOptions));

        $queries->expects($this->at(0))
            ->method('addQuery')
            ->with(
                new UpdateExtendConfigMigrationQuery(
                    $extendOptions,
                    $commandExecutor,
                    $optionsPath
                )
            );
        $queries->expects($this->at(1))
            ->method('addQuery')
            ->with(
                new RefreshExtendCacheMigrationQuery(
                    $commandExecutor
                )
            );

        $migration->up($schema, $queries);
    }
}
