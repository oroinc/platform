<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Psr\Log\NullLogger;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationArrayLogger;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;

class UpdateExtendConfigMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDescription()
    {
        $options = ['test'];

        $configProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $configProcessor
            ->expects($this->once())
            ->method('processConfigs')
            ->with($options, new UpdateExtendConfigMigrationArrayLogger(), true);

        $migrationQuery = new UpdateExtendConfigMigrationQuery($options, $configProcessor);

        $this->assertEquals([], $migrationQuery->getDescription());
    }

    public function testExecute()
    {
        $logger = new NullLogger();

        $configProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $configProcessor
            ->expects($this->once())
            ->method('processConfigs')
            ->with([], $logger);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $migrationQuery = new UpdateExtendConfigMigrationQuery([], $configProcessor);

        $migrationQuery->execute($connection, $logger);
    }
}
