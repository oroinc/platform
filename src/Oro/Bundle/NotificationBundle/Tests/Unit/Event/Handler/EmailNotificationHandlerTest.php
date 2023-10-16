<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\Handler\TemplateEmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailNotificationHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testHandle(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $additionalEmailAssociationProvider = $this->createMock(ChainAdditionalEmailAssociationProvider::class);

        $notification = $this->createMock(EmailNotification::class);
        $notifications = [$notification];
        $notificationsForManager = [
            new TemplateEmailNotificationAdapter(
                $entity,
                $notification,
                $doctrine,
                PropertyAccess::createPropertyAccessor(),
                $dispatcher,
                $additionalEmailAssociationProvider
            )
        ];

        $manager = $this->createMock(EmailNotificationManager::class);
        $manager->expects(self::once())
            ->method('process')
            ->with($notificationsForManager);

        $event = $this->createMock(NotificationEvent::class);
        $event->expects(self::any())
            ->method('getEntity')
            ->willReturn($entity);

        $handler = new EmailNotificationHandler(
            $manager,
            $doctrine,
            PropertyAccess::createPropertyAccessor(),
            $dispatcher,
            $additionalEmailAssociationProvider
        );
        $handler->handle($event, $notifications);
    }
}
