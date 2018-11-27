<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\Handler\TemplateEmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailNotificationHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * Test handler
     */
    public function testHandle()
    {
        $entity = $this->createMock(\stdClass::class);
        /** @var NotificationEvent | \PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(NotificationEvent::class);
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var EntityManager $em */
        $em = $this->createMock(EntityManager::class);

        /** @var EmailNotification $notification */
        $notification = $this->createMock(EmailNotification::class);
        $notifications = [$notification];
        $notificationsForManager = [
            new TemplateEmailNotificationAdapter(
                $entity,
                $notification,
                $em,
                $this->getPropertyAccessor(),
                $dispatcher
            )
        ];

        /** @var EmailNotificationManager | \PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->createMock(EmailNotificationManager::class);
        $manager->expects($this->once())
            ->method('process')
            ->with($this->equalTo($notificationsForManager));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($em);

        $handler = new EmailNotificationHandler($manager, $doctrine, $this->getPropertyAccessor(), $dispatcher);
        $handler->handle($event, $notifications);
    }
}
