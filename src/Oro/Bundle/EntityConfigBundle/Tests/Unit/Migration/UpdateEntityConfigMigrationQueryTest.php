<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigrationQuery;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

class UpdateEntityConfigMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager $cm
     */
    protected $cm;

    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    /**
     * @var UpdateEntityConfigMigrationQuery
     */
    protected $migrationQuery;

    public function setUp()
    {
        parent::setUp();

        $this->cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configDumper = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper')
            ->setConstructorArgs([$this->cm])
            ->getMock();
        $this->configDumper
            ->expects($this->once())
            ->method('updateConfigs');

        $this->migrationQuery = new UpdateEntityConfigMigrationQuery($this->configDumper);
    }

    public function testMigration()
    {
        /** @var Connection $connection */
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->migrationQuery->execute($connection);

        $this->assertEquals(
            'UPDATE ENTITY CONFIG',
            $this->migrationQuery->getDescription()
        );

        $this->assertAttributeInstanceOf(
            'Psr\Log\NullLogger',
            'logger',
            $this->configDumper
        );
    }
}
