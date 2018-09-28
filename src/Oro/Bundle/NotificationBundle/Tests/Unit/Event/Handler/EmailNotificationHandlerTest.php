<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\Handler\TemplateEmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailNotificationHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * Test handler
     */
    public function testHandle()
    {
        $entity = $this->createMock(\stdClass::class);
        /** @var NotificationEvent | \PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(NotificationEvent::class);
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $event->expects($this->any())
            ->method('getDispatcher')
            ->willReturn($dispatcher);

        /** @var EntityManager $em */
        $em = $this->createMock(EntityManager::class);

        /** @var EmailNotification $notification */
        $notification = $this->createMock(EmailNotification::class);
        $notifications = [$notification];
        $notificationsForManager = [
            new TemplateEmailNotificationAdapter($entity, $notification, $em, $this->getPropertyAccessor(), $dispatcher)
        ];

        /** @var EmailNotificationManager | \PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->createMock(EmailNotificationManager::class);
        $manager->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($entity), $this->equalTo($notificationsForManager));

        $handler = new EmailNotificationHandler($manager, $em, $this->getPropertyAccessor());
        $handler->handle($event, $notifications);
    }
}
