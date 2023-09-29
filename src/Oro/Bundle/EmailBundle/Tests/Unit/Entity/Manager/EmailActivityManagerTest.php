<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailActivityManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var EmailActivityListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityListProvider;

    /** @var EmailThreadProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadProvider;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var EntityOwnerAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOwnerAccessor;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailActivityManager */
    private $emailActivityManager;

    protected function setUp(): void
    {
        $this->activityManager = $this->createMock(ActivityManager::class);
        $this->emailActivityListProvider = $this->createMock(EmailActivityListProvider::class);
        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $entityOwnerAccessorLink = $this->createMock(ServiceLink::class);
        $entityOwnerAccessorLink->expects(self::any())
            ->method('getService')
            ->willReturn($this->entityOwnerAccessor);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->em);

        $this->emailActivityManager = new EmailActivityManager(
            $this->activityManager,
            $this->emailActivityListProvider,
            $this->emailThreadProvider,
            $this->tokenStorage,
            $entityOwnerAccessorLink,
            $doctrine
        );
    }

    private function getEmail(int $id = null, int $threadId = null): Email
    {
        $email = new Email();
        if (null !== $id) {
            ReflectionUtil::setId($email, $id);
        }

        if (null !== $threadId) {
            $thread = new EmailThread();
            ReflectionUtil::setId($thread, $threadId);
            $email->setThread($thread);
        }

        return $email;
    }

    private function addEmailSender(Email $email, ?EmailOwnerInterface $owner): void
    {
        $email->setFromEmailAddress($this->getEmailAddress($owner));
    }

    private function addEmailRecipient(Email $email, ?EmailOwnerInterface $owner): void
    {
        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($this->getEmailAddress($owner));
        $email->addRecipient($recipient);
    }

    private function getEmailAddress(?EmailOwnerInterface $owner): EmailAddress
    {
        $emailAddress = new EmailAddress();
        if (null !== $owner) {
            $emailAddress->setOwner($owner);
        }

        return $emailAddress;
    }

    private function getEmailAddressOwner(int $id): TestUser
    {
        $owner = new TestUser();
        $owner->setId($id);

        return $owner;
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        $organization->setId($id);

        return $organization;
    }

    public function testAddAssociation(): void
    {
        $email = $this->getEmail();
        $target = $this->getEmailAddressOwner(1);

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with(self::identicalTo($email), self::identicalTo($target))
            ->willReturn(true);

        self::assertTrue($this->emailActivityManager->addAssociation($email, $target));
    }

    public function testRemoveAssociation(): void
    {
        $email = $this->getEmail();
        $target = $this->getEmailAddressOwner(1);

        $this->activityManager->expects(self::once())
            ->method('removeActivityTarget')
            ->with(self::identicalTo($email), self::identicalTo($target))
            ->willReturn(true);

        self::assertTrue($this->emailActivityManager->removeAssociation($email, $target));
    }

    public function testUpdateActivitiesWhenNoCreatedEmails(): void
    {
        $this->emailActivityListProvider->expects(self::never())
            ->method('getTargetEntities');
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::never())
            ->method('getToken');
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::never())
            ->method('addActivityTarget');

        $this->emailActivityManager->updateActivities([]);
    }

    public function testUpdateActivitiesWhenEmailHasNoRecipients(): void
    {
        $email = $this->getEmail(1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $this->addEmailSender($email, $senderOwner);

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with(self::identicalTo($email), self::identicalTo($senderOwner))
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenEmailHasNoSender(): void
    {
        $email = $this->getEmail(1);
        $recipientOwner = $this->getEmailAddressOwner(1);
        $this->addEmailRecipient($email, $recipientOwner);

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with(self::identicalTo($email), self::identicalTo($recipientOwner))
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesForNonThreadedEmail(): void
    {
        $email = $this->getEmail(1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $recipientOwner = $this->getEmailAddressOwner(2);
        $this->addEmailSender($email, $senderOwner);
        $this->addEmailRecipient($email, $recipientOwner);

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::exactly(2))
            ->method('addActivityTarget')
            ->withConsecutive(
                [self::identicalTo($email), self::identicalTo($senderOwner)],
                [self::identicalTo($email), self::identicalTo($recipientOwner)]
            )
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenEmailHasSameOwnerForSenderAndSomeRecipients(): void
    {
        $email = $this->getEmail(1);
        $owner1 = $this->getEmailAddressOwner(1);
        $owner2 = $this->getEmailAddressOwner(2);
        $owner3 = $this->getEmailAddressOwner(3);
        $owner4 = $this->getEmailAddressOwner(4);
        $this->addEmailSender($email, $owner1);
        $this->addEmailRecipient($email, $owner2);
        $this->addEmailRecipient($email, $owner3);
        $this->addEmailRecipient($email, $owner4);
        $this->addEmailRecipient($email, $owner1);
        $this->addEmailRecipient($email, $owner2);
        $this->addEmailRecipient($email, null);

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::exactly(4))
            ->method('addActivityTarget')
            ->withConsecutive(
                [self::identicalTo($email), self::identicalTo($owner1)],
                [self::identicalTo($email), self::identicalTo($owner2)],
                [self::identicalTo($email), self::identicalTo($owner3)],
                [self::identicalTo($email), self::identicalTo($owner4)]
            )
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenSenderFromAnotherOrganization(): void
    {
        $email = $this->getEmail(1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $recipientOwner = $this->getEmailAddressOwner(2);
        $this->addEmailSender($email, $senderOwner);
        $this->addEmailRecipient($email, $recipientOwner);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects(self::atLeastOnce())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(1));

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($token);
        $this->entityOwnerAccessor->expects(self::exactly(2))
            ->method('getOrganization')
            ->willReturnMap([
                [$senderOwner, $this->getOrganization(2)],
                [$recipientOwner, $this->getOrganization(1)]
            ]);

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with(self::identicalTo($email), self::identicalTo($recipientOwner))
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenRecipientFromAnotherOrganization(): void
    {
        $email = $this->getEmail(1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $recipientOwner = $this->getEmailAddressOwner(2);
        $this->addEmailSender($email, $senderOwner);
        $this->addEmailRecipient($email, $recipientOwner);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects(self::atLeastOnce())
            ->method('getOrganization')
            ->willReturn($this->getOrganization(1));

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($token);
        $this->entityOwnerAccessor->expects(self::exactly(2))
            ->method('getOrganization')
            ->willReturnMap([
                [$senderOwner, $this->getOrganization(1)],
                [$recipientOwner, $this->getOrganization(2)]
            ]);

        $this->activityManager->expects(self::once())
            ->method('addActivityTarget')
            ->with(self::identicalTo($email), self::identicalTo($senderOwner))
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenEmailAndReferencedEmailDoesNotHaveContexts(): void
    {
        $email = $this->getEmail(1, 1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $recipientOwner = $this->getEmailAddressOwner(2);
        $this->addEmailSender($email, $senderOwner);
        $this->addEmailRecipient($email, $recipientOwner);

        $referencedEmail = $this->getEmail(2, 1);

        $this->emailActivityListProvider->expects(self::exactly(2))
            ->method('getTargetEntities')
            ->withConsecutive(
                [self::identicalTo($email)],
                [self::identicalTo($referencedEmail)]
            )
            ->willReturn([]);
        $this->emailThreadProvider->expects(self::once())
            ->method('getEmailReferences')
            ->with(self::identicalTo($this->em), self::identicalTo($email))
            ->willReturn([$referencedEmail]);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::exactly(2))
            ->method('addActivityTarget')
            ->withConsecutive(
                [self::identicalTo($email), self::identicalTo($senderOwner)],
                [self::identicalTo($email), self::identicalTo($recipientOwner)]
            )
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenEmailHasContexts(): void
    {
        $email = $this->getEmail(1, 1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $recipientOwner1 = $this->getEmailAddressOwner(2);
        $recipientOwner2 = $this->getEmailAddressOwner(3);
        $this->addEmailSender($email, $senderOwner);
        $this->addEmailRecipient($email, $recipientOwner1);
        $this->addEmailRecipient($email, $recipientOwner2);

        $this->emailActivityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->with(self::identicalTo($email))
            ->willReturn([$this->getEmailAddressOwner(10)]);
        $this->emailThreadProvider->expects(self::never())
            ->method('getEmailReferences');

        $this->tokenStorage->expects(self::never())
            ->method('getToken');
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::never())
            ->method('addActivityTarget');

        $this->emailActivityManager->updateActivities([$email]);
    }

    public function testUpdateActivitiesWhenEmailDoesNotHaveContextsAndReferencedEmailHasContexts(): void
    {
        $email = $this->getEmail(1, 1);
        $senderOwner = $this->getEmailAddressOwner(1);
        $recipientOwner = $this->getEmailAddressOwner(2);
        $this->addEmailSender($email, $senderOwner);
        $this->addEmailRecipient($email, $recipientOwner);

        $referencedEmail1 = $this->getEmail(2, 1);
        $referencedEmail1SenderOwner = $this->getEmailAddressOwner(20);
        $referencedEmail1RecipientOwner = $this->getEmailAddressOwner(21);
        $this->addEmailSender($referencedEmail1, $referencedEmail1SenderOwner);
        $this->addEmailRecipient($referencedEmail1, $referencedEmail1RecipientOwner);

        $referencedEmail2 = $this->getEmail(3, 1);
        $referencedEmail2SenderOwner = $this->getEmailAddressOwner(30);
        $referencedEmail2RecipientOwner = $this->getEmailAddressOwner(31);
        $this->addEmailSender($referencedEmail2, $referencedEmail2SenderOwner);
        $this->addEmailRecipient($referencedEmail2, $referencedEmail2RecipientOwner);

        $referencedEmail1Context = $this->getEmailAddressOwner(100);
        $referencedEmail2Context = $this->getEmailAddressOwner(110);

        $this->emailActivityListProvider->expects(self::exactly(3))
            ->method('getTargetEntities')
            ->withConsecutive(
                [self::identicalTo($email)],
                [self::identicalTo($referencedEmail1)],
                [self::identicalTo($referencedEmail2)]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [
                    $senderOwner,
                    $recipientOwner,
                    $referencedEmail1Context,
                    $referencedEmail1SenderOwner,
                    $referencedEmail1RecipientOwner
                ],
                [
                    $senderOwner,
                    $recipientOwner,
                    $referencedEmail2Context,
                    $referencedEmail2SenderOwner,
                    $referencedEmail2RecipientOwner
                ]
            );
        $this->emailThreadProvider->expects(self::once())
            ->method('getEmailReferences')
            ->with(self::identicalTo($this->em), self::identicalTo($email))
            ->willReturn([$referencedEmail1, $referencedEmail2]);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn(null);
        $this->entityOwnerAccessor->expects(self::never())
            ->method('getOrganization');

        $this->activityManager->expects(self::exactly(4))
            ->method('addActivityTarget')
            ->withConsecutive(
                [self::identicalTo($email), self::identicalTo($senderOwner)],
                [self::identicalTo($email), self::identicalTo($recipientOwner)],
                [self::identicalTo($email), self::identicalTo($referencedEmail1Context)],
                [self::identicalTo($email), self::identicalTo($referencedEmail2Context)]
            )
            ->willReturn(true);

        $this->emailActivityManager->updateActivities([$email]);
    }
}
