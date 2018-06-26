<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials;

use Oro\Bundle\ImapBundle\OriginSyncCredentials\SyncCredentialsIssueManager;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\WrongCredentialsOriginsDriverInterface;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestNotificationSender;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SyncCredentialsIssueManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SyncCredentialsIssueManager */
    private $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $credentialsDriver;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;


    protected function setUp()
    {
        $this->credentialsDriver = $this->createMock(WrongCredentialsOriginsDriverInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->manager = new SyncCredentialsIssueManager($this->credentialsDriver, $this->authorizationChecker);
    }

    public function testAddInvalidSystemOrigin()
    {
        $origin = new TestUserEmailOrigin(123);

        $this->credentialsDriver->expects($this->once())
            ->method('addOrigin')
            ->with(123, null);

        $this->manager->addInvalidOrigin($origin);
    }

    public function testAddInvalidOrigin()
    {
        $origin = new TestUserEmailOrigin(123);
        $user = new User();
        $user->setId(456);
        $origin->setOwner($user);

        $this->credentialsDriver->expects($this->once())
            ->method('addOrigin')
            ->with(123, 456);

        $this->manager->addInvalidOrigin($origin);
    }

    public function testProcessInvalidOrigins()
    {
        $origin = new TestUserEmailOrigin(123);

        $notificationSender = new TestNotificationSender();
        $this->manager->addNotificationSender($notificationSender);

        $this->credentialsDriver->expects($this->once())
            ->method('getAllOrigins')
            ->willReturn([$origin]);
        $this->credentialsDriver->expects($this->once())
            ->method('deleteAllOrigins');

        $processedOrigins = $this->manager->processInvalidOrigins();

        $this->assertEquals([$origin], $notificationSender->processedOrigins);
        $this->assertEquals([$origin], $processedOrigins);
    }

    public function testProcessInvalidOriginsForUserWithoutPermissionToGetSystemOriginNotifications()
    {
        $origin = new TestUserEmailOrigin(123);
        $user = new User();
        $user->setId(456);

        $notificationSender = new TestNotificationSender();
        $this->manager->addUserNotificationSender($notificationSender);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_imap_sync_origin_credential_notifications')
            ->willReturn(false);

        $this->credentialsDriver->expects($this->once())
            ->method('getAllOriginsByOwnerId')
            ->with(456)
            ->willReturn([$origin]);
        $this->credentialsDriver->expects($this->once())
            ->method('deleteOrigin')
            ->with(123);

        $this->manager->processInvalidOriginsForUser($user);

        $this->assertEquals([$origin], $notificationSender->processedOrigins);
    }

    public function testProcessInvalidOriginsForUserWithPermissionToGetSystemOriginNotifications()
    {
        $origin = new TestUserEmailOrigin(123);
        $user = new User();
        $user->setId(456);

        $systemOrigin = new TestUserEmailOrigin(852);

        $notificationSender = new TestNotificationSender();
        $this->manager->addUserNotificationSender($notificationSender);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_imap_sync_origin_credential_notifications')
            ->willReturn(true);

        $this->credentialsDriver->expects($this->exactly(2))
            ->method('getAllOriginsByOwnerId')
            ->willReturnMap(
                [
                    [456, [$origin]],
                    [null, [$systemOrigin]]
                ]
            );
        $this->credentialsDriver->expects($this->exactly(2))
            ->method('deleteOrigin');

        $this->manager->processInvalidOriginsForUser($user);

        $this->assertEquals([$origin, $systemOrigin], $notificationSender->processedOrigins);
    }

    public function testRemoveOriginFromTheFailed()
    {
        $origin = new TestUserEmailOrigin(123);
        $this->credentialsDriver->expects($this->once())
            ->method('deleteOrigin')
            ->with(123);

        $this->manager->removeOriginFromTheFailed($origin);
    }
}
