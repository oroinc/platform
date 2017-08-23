<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Bundle\EmailBundle\Async\Topics as EmailTopics;
use Oro\Bundle\EmailBundle\EventListener\EmailAssociationsDemoDataFixturesListener;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class EmailAssociationsDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $messageProducer;

    /** @var EmailAssociationsDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new EmailAssociationsDemoDataFixturesListener(
            $this->listenerManager,
            $this->messageProducer
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
        $this->messageProducer->expects(self::never())
            ->method('send');

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
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(EmailTopics::UPDATE_ASSOCIATIONS_TO_EMAILS, []);

        $this->listener->onPostLoad($event);
    }
}
