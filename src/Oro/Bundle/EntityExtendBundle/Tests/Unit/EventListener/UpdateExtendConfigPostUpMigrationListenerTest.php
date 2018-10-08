<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityExtendBundle\EventListener\UpdateExtendConfigPostUpMigrationListener;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateExtendConfigPostUpMigrationListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnPostUp()
    {
        $optionsPath = realpath(__DIR__ . '/../Fixtures') . '/test_options.yml';
        $initialStatePath = realpath(__DIR__ . '/../Fixtures') . '/initial_state.yml';
        $commandExecutor = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor')
            ->disableOriginalConstructor()
            ->getMock();

        $postUpMigrationListener = new UpdateExtendConfigPostUpMigrationListener(
            $commandExecutor,
            $optionsPath,
            $initialStatePath
        );

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $event = new PostMigrationEvent($connection);

        $event->addMigration(new TestMigration());

        $postUpMigrationListener->onPostUp($event);

        $migrations = $event->getMigrations();
        $this->assertCount(2, $migrations);
        $this->assertEquals(
            new TestMigration(),
            $migrations[0]
        );
        $this->assertEquals(
            new UpdateExtendConfigMigration(
                $commandExecutor,
                $optionsPath,
                $initialStatePath
            ),
            $migrations[1]
        );
    }
}
