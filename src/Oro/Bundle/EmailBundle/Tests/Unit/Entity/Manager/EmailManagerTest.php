<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private EmailThreadProvider&MockObject $emailThreadProvider;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private EmailManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(EmailUser::class)
            ->willReturn($this->em);

        $mailboxManager = $this->createMock(MailboxManager::class);
        $mailboxManager->expects(self::any())
            ->method('findAvailableMailboxIds')
            ->willReturn([]);

        $this->manager = new EmailManager(
            $doctrine,
            $this->emailThreadProvider,
            $this->tokenAccessor,
            $mailboxManager
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testSetEmailSeenChanges(
        bool $isSeen,
        bool $newSeen,
        bool $seen,
        bool $flush,
        int $calls,
        int $flushCalls
    ): void {
        $emailUser = $this->createMock(EmailUser::class);
        $emailUser->expects(self::once())
            ->method('isSeen')
            ->willReturn($isSeen);
        $emailUser->expects(self::exactly($calls))
            ->method('setSeen')
            ->with($newSeen);
        $this->em->expects(self::exactly($flushCalls))
            ->method('flush');

        $this->manager->setEmailUserSeen($emailUser, $seen, $flush);
    }

    public function dataProvider(): array
    {
        return [
            'unseen when seen with flush' => [
                'isSeen' => true,
                'newSeen' => false,
                'seen' => false,
                'flush' => true,
                'calls' => 1,
                'flushCalls' => 1
            ],
            'unseen when unseen with flush' => [
                'isSeen' => false,
                'newSeen' => false,
                'seen' => false,
                'flush' => true,
                'calls' => 0,
                'flushCalls' => 0
            ],
            'seen when unseen with flush' => [
                'isSeen' => false,
                'newSeen' => true,
                'seen' => true,
                'flush' => true,
                'calls' => 1,
                'flushCalls' => 1
            ],
            'seen when seen with flush' => [
                'isSeen' => true,
                'newSeen' => true,
                'seen' => true,
                'flush' => true,
                'calls' => 0,
                'flushCalls' => 0
            ],
            'seen when unseen without flush' => [
                'isSeen' => false,
                'newSeen' => true,
                'seen' => true,
                'flush' => false,
                'calls' => 1,
                'flushCalls' => 0
            ]
        ];
    }

    public function testSetEmailSeenChangesDefs(): void
    {
        $emailUser = $this->createMock(EmailUser::class);
        $emailUser->expects(self::once())
            ->method('isSeen')
            ->willReturn(false);
        $emailUser->expects(self::once())
            ->method('setSeen')
            ->with(true);
        $this->em->expects(self::never())
            ->method('flush');

        $this->manager->setEmailUserSeen($emailUser);
    }

    public function testToggleEmailUserSeen(): void
    {
        $threadArray = [new EmailUser()];

        $emailUser = $this->createMock(EmailUser::class);
        $email = $this->createMock(Email::class);
        $emailThread = $this->createMock(EmailThread::class);
        $emailUser->expects(self::exactly(2))
            ->method('getEmail')
            ->willReturn($email);
        $email->expects(self::exactly(2))
            ->method('getThread')
            ->willReturn($emailThread);
        $emailThread->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $emailUser->expects(self::once())
            ->method('setSeen')
            ->with(false);
        $emailUser->expects(self::exactly(2))
            ->method('isSeen')
            ->willReturn(true);
        $emailUser->expects(self::exactly(2))
            ->method('getOwner')
            ->willReturn(new User());

        $this->em->expects(self::once())
            ->method('flush');
        $this->em->expects(self::exactly(2))
            ->method('persist');

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($threadArray);

        $repository = $this->createMock(EmailUserRepository::class);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('getEmailUserByThreadId')
            ->willReturn($qb);

        $this->manager->toggleEmailUserSeen($emailUser);
    }

    public function testMarkAllEmailsAsSeenEmpty(): void
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([]);

        $repository = $this->createMock(EmailUserRepository::class);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findUnseenUserEmail')
            ->willReturn($qb);

        $this->manager->markAllEmailsAsSeen($user, $organization);
    }

    /**
     * @dataProvider dataSeenProvider
     */
    public function testMarkAllEmailsAsSeen(bool $isSeen, int $setSeenCalls): void
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $emailUser = $this->createMock(EmailUser::class);
        $emailUser->expects(self::once())
            ->method('isSeen')
            ->willReturn($isSeen);
        $emailUser->expects(self::exactly($setSeenCalls))
            ->method('setSeen')
            ->with(true);
        $this->em->expects(self::once())
            ->method('flush');

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$emailUser]);

        $repository = $this->createMock(EmailUserRepository::class);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('findUnseenUserEmail')
            ->willReturn($qb);

        $this->manager->markAllEmailsAsSeen($user, $organization);
    }

    public function testSetSeenStatus(): void
    {
        $user = new User();
        $organization = new Organization();
        $email = new Email();
        $emailUsers = [
            new EmailUser(),
            new EmailUser(),
            new EmailUser()
        ];

        array_map(
            function (EmailUser $emailUser) use ($email) {
                $emailUser->setEmail($email);
                self::assertFalse($emailUser->isSeen());
            },
            $emailUsers
        );

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $emailUsersRepo = $this->createMock(EmailUserRepository::class);
        $emailUsersRepo->expects(self::once())
            ->method('getAllEmailUsersByEmail')
            ->with($email, $user, $organization, false)
            ->willReturn($emailUsers);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(EmailUser::class)
            ->willReturn($emailUsersRepo);

        $this->manager->setSeenStatus($email, true);

        array_map(
            function (EmailUser $emailUser) {
                self::assertTrue($emailUser->isSeen());
            },
            $emailUsers
        );
    }

    public function dataSeenProvider(): array
    {
        return [
            'seen' => [
                'isSeen' => true,
                'setSeenCalls' => 0
            ],
            'unseen to seen' => [
                'isSeen' => false,
                'setSeenCalls' => 1,
            ]
        ];
    }
}
