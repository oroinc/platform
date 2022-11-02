<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Oro\Bundle\EntityConfigBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\WarmUpEntityConfigCacheMigration;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testUpdateConfigs()
    {
        $commandExecutor = $this->createMock(CommandExecutor::class);

        $postUpMigrationListener = new PostUpMigrationListener($commandExecutor);

        $connection = $this->createMock(Connection::class);

        $event = new PostMigrationEvent($connection);
        $event->addMigration(new TestMigration());

        $postUpMigrationListener->updateConfigs($event);

        $migrations = $event->getMigrations();

        $this->assertNotEmpty($event);
        $this->assertCount(2, $migrations);

        $this->assertInstanceOf(
            TestMigration::class,
            $migrations[0]
        );
        $this->assertInstanceOf(
            UpdateEntityConfigMigration::class,
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
        $commandExecutor = $this->createMock(CommandExecutor::class);

        $postUpMigrationListener = new PostUpMigrationListener($commandExecutor);

        $connection = $this->createMock(Connection::class);

        $event = new PostMigrationEvent($connection);
        $event->addMigration(new TestMigration());

        $postUpMigrationListener->warmUpCache($event);

        $migrations = $event->getMigrations();

        $this->assertNotEmpty($event);
        $this->assertCount(2, $migrations);

        $this->assertInstanceOf(
            TestMigration::class,
            $migrations[0]
        );
        $this->assertInstanceOf(
            WarmUpEntityConfigCacheMigration::class,
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
