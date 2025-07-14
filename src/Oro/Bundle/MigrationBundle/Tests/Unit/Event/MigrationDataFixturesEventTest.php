<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use PHPUnit\Framework\TestCase;

class MigrationDataFixturesEventTest extends TestCase
{
    public function testEventWithoutLogger(): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $fixturesType = 'test';

        $event = new MigrationDataFixturesEvent($manager, $fixturesType);

        self::assertSame($manager, $event->getObjectManager());
        self::assertEquals($fixturesType, $event->getFixturesType());
        // test that no exception when logger is not set
        $event->log('some message');
    }

    public function testEventWithLogger(): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $fixturesType = 'test';
        $logMessages = [];
        $logger = function ($message) use (&$logMessages) {
            $logMessages[] = $message;
        };

        $event = new MigrationDataFixturesEvent($manager, $fixturesType, $logger);

        self::assertSame($manager, $event->getObjectManager());
        self::assertEquals($fixturesType, $event->getFixturesType());
        // test logger
        $event->log('some message');
        self::assertEquals(['some message'], $logMessages);
    }

    public function testFixturesType(): void
    {
        $fixturesType = 'test';

        $event = new MigrationDataFixturesEvent($this->createMock(ObjectManager::class), $fixturesType);

        self::assertEquals($fixturesType, $event->getFixturesType());
        self::assertFalse($event->isMainFixtures());
        self::assertFalse($event->isDemoFixtures());
    }

    public function testMainFixturesType(): void
    {
        $event = new MigrationDataFixturesEvent(
            $this->createMock(ObjectManager::class),
            DataFixturesExecutorInterface::MAIN_FIXTURES
        );

        self::assertEquals(DataFixturesExecutorInterface::MAIN_FIXTURES, $event->getFixturesType());
        self::assertTrue($event->isMainFixtures());
        self::assertFalse($event->isDemoFixtures());
    }

    public function testDemoFixturesType(): void
    {
        $event = new MigrationDataFixturesEvent(
            $this->createMock(ObjectManager::class),
            DataFixturesExecutorInterface::DEMO_FIXTURES
        );

        self::assertEquals(DataFixturesExecutorInterface::DEMO_FIXTURES, $event->getFixturesType());
        self::assertFalse($event->isMainFixtures());
        self::assertTrue($event->isDemoFixtures());
    }
}
