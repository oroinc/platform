<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\Handler\TemplateEmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Helper\WebsiteAwareEntityHelper;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailNotificationHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle()
    {
        $websiteAware = $this->createMock(WebsiteAwareEntityHelper::class);
        $entity = $this->createMock(\stdClass::class);
        $event = $this->createMock(NotificationEvent::class);
        $event->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $em = $this->createMock(EntityManager::class);

        $additionalEmailAssociationProvider = $this->createMock(ChainAdditionalEmailAssociationProvider::class);

        $notification = $this->createMock(EmailNotification::class);
        $notifications = [$notification];
        $notificationsForManager = [
            new TemplateEmailNotificationAdapter(
                $entity,
                $notification,
                $em,
                PropertyAccess::createPropertyAccessor(),
                $dispatcher,
                $additionalEmailAssociationProvider,
                $websiteAware
            )
        ];

        $manager = $this->createMock(EmailNotificationManager::class);
        $manager->expects($this->once())
            ->method('process')
            ->with($this->equalTo($notificationsForManager));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($em);

        $handler = new EmailNotificationHandler(
            $manager,
            $doctrine,
            PropertyAccess::createPropertyAccessor(),
            $dispatcher,
            $additionalEmailAssociationProvider,
            $websiteAware
        );
        $handler->handle($event, $notifications);
    }
}
