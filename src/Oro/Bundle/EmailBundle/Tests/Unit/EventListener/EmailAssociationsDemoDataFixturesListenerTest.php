<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EmailAssociationsDemoDataFixturesListener;
use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class EmailAssociationsDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var AssociationManager|\PHPUnit_Framework_MockObject_MockObject */
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
    }

    public function testOnPreLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('disableListener');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('disableListener')
            ->with(EmailAssociationsDemoDataFixturesListener::ENTITY_LISTENER);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('enableListener');
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
            ->method('enableListener')
            ->with(EmailAssociationsDemoDataFixturesListener::ENTITY_LISTENER);
        $this->associationManager->expects(self::once())
            ->method('processUpdateAllEmailOwners');

        $this->listener->onPostLoad($event);
    }
}
