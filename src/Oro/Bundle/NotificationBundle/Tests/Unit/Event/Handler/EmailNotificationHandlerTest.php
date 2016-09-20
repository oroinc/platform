<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\TagBundle\Entity\Taggable;

class EmailNotificationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test handler
     */
    public function testHandle()
    {
        $entity = $this->getMock(Taggable::class);
        /** @var NotificationEvent | \PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(NotificationEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        /** @var EntityManager $em */
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigProvider $configProvider */
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EmailNotification $notification */
        $notification = $this->getMock(EmailNotification::class);
        $notifications = [$notification];
        $notificationsForManager = [new EmailNotificationAdapter($entity, $notification, $em, $configProvider)];

        /** @var EmailNotificationManager | \PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->getMockBuilder(EmailNotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($entity), $this->equalTo($notificationsForManager));

        $handler = new EmailNotificationHandler($manager, $em, $configProvider);
        $handler->handle($event, $notifications);
    }
}
