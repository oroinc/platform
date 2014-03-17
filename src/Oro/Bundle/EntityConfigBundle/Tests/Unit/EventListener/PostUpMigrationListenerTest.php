<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PostUpMigrationListener $postUpMigrationListener */
        $postUpMigrationListener = new PostUpMigrationListener(
            $commandExecutor
        );

        /** @var Connection $connection */
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var PostMigrationEvent $event */
        $event = new PostMigrationEvent($connection);
        $event->addMigration(new TestMigration());

        $postUpMigrationListener->onPostUp($event);

        $migrations = $event->getMigrations();

        $this->assertNotEmpty($event);
        $this->assertCount(2, $migrations);

        $this->assertInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration',
            $migrations[0]
        );
        $this->assertInstanceOf(
            'Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration',
            $migrations[1]
        );
        $this->assertEquals(
            new TestMigration(),
            $migrations[0]
        );
        $this->assertEquals(
            new UpdateEntityConfigMigration($commandExecutor),
            $migrations[1]
        );

    }
}
