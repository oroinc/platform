<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityExtendBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration;
use Oro\Bundle\EntityExtendBundle\Tools\DbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        /** @var ConfigManager $cm */
        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OroEntityManager $em */
        $em = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $nameGenerator = new DbIdentifierNameGenerator();

        /** @var PostUpMigrationListener $postUpMigrationListener */
        $postUpMigrationListener = new PostUpMigrationListener(
            new ExtendConfigProcessor($cm),
            new ExtendConfigDumper($em, $nameGenerator, '')
        );

        /** @var Connection $connection */
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PostMigrationEvent $event */
        $event = new PostMigrationEvent($connection);
        $event->addMigration(new TestMigration());

        $postUpMigrationListener->onPostUp($event);

        $this->assertNotEmpty($event);
        $this->assertInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration',
            $event->getMigrations()[0]
        );
        $this->assertInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration',
            $event->getMigrations()[1]
        );
    }
}
