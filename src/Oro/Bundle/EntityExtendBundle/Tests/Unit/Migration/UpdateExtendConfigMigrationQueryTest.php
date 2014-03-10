<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;


use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationArrayLogger;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationQuery;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

class UpdateExtendConfigMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessConfigs()
    {
        /** @var ConfigManager $cm */
        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OroEntityManager $em */
        $em = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $nameGenerator = new ExtendDbIdentifierNameGenerator();

        $configProcessor = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor')
            ->setConstructorArgs([$cm])
            ->setMethods([])
            ->getMock();

        $configProcessor
            ->expects($this->once())
            ->method('processConfigs')
            ->with([], new UpdateExtendConfigMigrationArrayLogger(), true);

        /** @var ExtendConfigDumper $configDumper */
        $configDumper = new ExtendConfigDumper($em, $nameGenerator, '');

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

        /*$this->assertAttributeInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigrationArrayLogger',
            'logger',
            $configProcessor
        );

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor',
            'configProcessor',
            $migrationQuery
        );*/

    }
}
