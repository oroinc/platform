<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        /** @var ConfigManager $cm */
        $cm = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PostUpMigrationListener $postUpMigrationListener */
        $postUpMigrationListener = new PostUpMigrationListener(
            new ConfigDumper($cm)
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
            'Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration',
            $event->getMigrations()[1]
        );
    }
}
