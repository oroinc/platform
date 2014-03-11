<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationArrayLogger;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class UpdateExtendConfigMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager $cm */
    protected $cm;

    /** @var OroEntityManager $em */
    protected $em;

    /** @var  ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    public function setUp()
    {
        parent::setUp();

        $this->cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();
    }

    public function testProcessConfigs()
    {
        $configProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor')
            ->setConstructorArgs([$this->cm])
            ->setMethods([])
            ->getMock();

        $configProcessor
            ->expects($this->once())
            ->method('processConfigs')
            ->with([], new UpdateExtendConfigMigrationArrayLogger(), true);

        /** @var ExtendConfigDumper $configDumper */
        $configDumper = new ExtendConfigDumper($this->em, $this->nameGenerator, '');

        /** @var UpdateExtendConfigMigrationQuery $postUpMigrationListener */
        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            [],
            $configProcessor,
            $configDumper
        );

        $this->assertEquals(
            [],
            $migrationQuery->getDescription()
        );
    }

    public function testProcessConfigs2()
    {
        $configProcessor = new ExtendConfigProcessor($this->cm);

        /** @var ExtendConfigDumper $configDumper */
        $configDumper = new ExtendConfigDumper($this->em, $this->nameGenerator, '');

        /** @var UpdateExtendConfigMigrationQuery $postUpMigrationListener */
        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            [],
            $configProcessor,
            $configDumper
        );

        $migrationQuery->getDescription();

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationArrayLogger',
            'logger',
            $configProcessor
        );

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor',
            'configProcessor',
            $migrationQuery
        );
    }

    public function testExecute()
    {
        $configProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor')
            ->setConstructorArgs([$this->cm])
            ->getMock();
        $configProcessor
            ->expects($this->once())
            ->method('processConfigs')
            ->with([]);

        /** @ var ExtendConfigDumper $configDumper */
        $configDumper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper')
            ->setConstructorArgs([$this->em, $this->nameGenerator, ''])
            ->getMock();
        $configDumper
            ->expects($this->once())
            ->method('updateConfig');
        $configDumper
            ->expects($this->once())
            ->method('dump');

        /** @var UpdateExtendConfigMigrationQuery $postUpMigrationListener */
        $migrationQuery = new UpdateExtendConfigMigrationQuery(
            [],
            $configProcessor,
            $configDumper
        );

        /** @var Connection $connection */
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $migrationQuery->execute($connection);
    }
}
