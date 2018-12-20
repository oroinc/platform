<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\EmailBundle\EventListener\EmailAssociationsDemoDataFixturesListener;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class EmailAssociationsDemoDataFixturesListenerTest extends \PHPUnit\Framework\TestCase
{
    const LISTENERS = [
        'test_listener_1',
        'test_listener_2',
    ];

    /** @var OptionalListenerManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $listenerManager;

    /** @var AssociationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $associationManager;

    /** @var EmailAssociationsDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->associationManager = $this->createMock(AssociationManager::class);

        $this->listener = new EmailAssociationsDemoDataFixturesListener(
            $this->listenerManager,
            $this->associationManager
        );
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    public function testOnPreLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('disableListeners');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('enableListeners');
        $this->associationManager->expects(self::never())
            ->method('processUpdateAllEmailOwners');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);
        $this->associationManager->expects(self::once())
            ->method('processUpdateAllEmailOwners');

        $this->listener->onPostLoad($event);
    }
}
