<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Sync\NotificationAlertManager;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\UserEmailOriginListener;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserEmailOriginListenerTest extends TestCase
{
    use EntityTrait;

    private EntityManagerInterface&MockObject $em;
    private NotificationAlertManager&MockObject $notificationAlertManager;
    private UserEmailOriginListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);

        $this->listener = new UserEmailOriginListener($this->notificationAlertManager);
    }

    public function testPrePersistOnEmptyOriginFolders(): void
    {
        $origin = new UserEmailOrigin();

        $this->em->expects(self::never())
            ->method('persist');

        $this->listener->prePersist($origin, new LifecycleEventArgs($origin, $this->em));
    }

    public function testPrePersistWithAlreadySavedFolders(): void
    {
        $folder1 = $this->getEntity(EmailFolder::class, ['id' => 1]);
        $folder2 = $this->getEntity(EmailFolder::class, ['id' => 2]);
        $origin = new UserEmailOrigin();
        $origin->addFolder($folder1);
        $origin->addFolder($folder2);

        $this->em->expects(self::never())
            ->method('persist');

        $this->listener->prePersist($origin, new LifecycleEventArgs($origin, $this->em));
    }

    public function testPrePersistWithNewFolders(): void
    {
        $folder1 = $this->getEntity(EmailFolder::class, ['id' => 1]);
        $folder2 = new EmailFolder();
        $origin = new UserEmailOrigin();
        $origin->addFolder($folder1);
        $origin->addFolder($folder2);

        $expectedImapEmailFolder = new ImapEmailFolder();
        $expectedImapEmailFolder->setUidValidity(0);
        $expectedImapEmailFolder->setFolder($folder2);

        $this->em->expects(self::once())
            ->method('persist')
            ->with($expectedImapEmailFolder);

        $this->listener->prePersist($origin, new LifecycleEventArgs($origin, $this->em));
    }

    public function testPreUpdateWithNonChangedRefreshToken(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setIsSyncEnabled(false);
        $changeSet = [];

        $event = new PreUpdateEventArgs($origin, $this->em, $changeSet);

        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeForCurrentUser');

        $this->listener->preUpdate($origin, $event);
        self::assertFalse($origin->isSyncEnabled());
    }

    public function testPreUpdateWithChangedRefreshTokenAndEnabledOrigin(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setIsSyncEnabled(true);
        $changeSet = ['refreshToken' => ['old', 'new']];

        $event = new PreUpdateEventArgs($origin, $this->em, $changeSet);

        $this->notificationAlertManager->expects(self::never())
            ->method('resolveNotificationAlertsByAlertTypeForCurrentUser');

        $this->listener->preUpdate($origin, $event);
        self::assertTrue($origin->isSyncEnabled());
    }

    public function testPreUpdateWithChangedRefreshToken(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setIsSyncEnabled(false);
        $changeSet = ['refreshToken' => ['old', 'new']];

        $event = new PreUpdateEventArgs($origin, $this->em, $changeSet);

        $metadata = new ClassMetadata(UserEmailOrigin::class);
        $uow = $this->createMock(UnitOfWork::class);

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $this->em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($metadata, $origin);
        $this->notificationAlertManager->expects(self::exactly(2))
            ->method('resolveNotificationAlertsByAlertTypeForCurrentUser');

        $this->listener->preUpdate($origin, $event);
        self::assertTrue($origin->isSyncEnabled());
    }
}
