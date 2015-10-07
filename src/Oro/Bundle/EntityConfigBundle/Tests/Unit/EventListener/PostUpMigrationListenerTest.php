<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\WarmUpEntityConfigCacheMigration;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdateConfigs()
    {
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $postUpMigrationListener = new PostUpMigrationListener($commandExecutor);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PostMigrationEvent($connection);
        $event->addMigration(new TestMigration());

        $postUpMigrationListener->updateConfigs($event);

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

    public function testWarmUpCache()
    {
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $postUpMigrationListener = new PostUpMigrationListener($commandExecutor);

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PostMigrationEvent($connection);
        $event->addMigration(new TestMigration());

        $postUpMigrationListener->warmUpCache($event);

        $migrations = $event->getMigrations();

        $this->assertNotEmpty($event);
        $this->assertCount(2, $migrations);

        $this->assertInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration',
            $migrations[0]
        );
        $this->assertInstanceOf(
            'Oro\Bundle\EntityConfigBundle\Migration\WarmUpEntityConfigCacheMigration',
            $migrations[1]
        );
        $this->assertEquals(
            new TestMigration(),
            $migrations[0]
        );
        $this->assertEquals(
            new WarmUpEntityConfigCacheMigration($commandExecutor),
            $migrations[1]
        );
    }
}
