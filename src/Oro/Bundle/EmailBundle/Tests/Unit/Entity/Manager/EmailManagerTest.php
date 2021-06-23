<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class EmailManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailThreadManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadManager;

    /** @var EmailThreadProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailThreadProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EmailManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->emailThreadManager = $this->createMock(EmailThreadManager::class);
        $this->emailThreadProvider = $this->createMock(EmailThreadProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $mailboxManager = $this->createMock(MailboxManager::class);
        $mailboxManager->expects($this->any())
            ->method('findAvailableMailboxIds')
            ->willReturn([]);

        $this->manager = new EmailManager(
            $this->em,
            $this->emailThreadManager,
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
    ) {
        $emailUser = $this->createMock(EmailUser::class);
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->willReturn($isSeen);
        $emailUser->expects($this->exactly($calls))
            ->method('setSeen')
            ->with($newSeen);
        $this->em->expects($this->exactly($flushCalls))
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

    public function testSetEmailSeenChangesDefs()
    {
        $emailUser = $this->createMock(EmailUser::class);
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->willReturn(false);
        $emailUser->expects($this->once())
            ->method('setSeen')
            ->with(true);
        $this->em->expects($this->never())
            ->method('flush');

        $this->manager->setEmailUserSeen($emailUser);
    }

    public function testToggleEmailUserSeen()
    {
        $threadArray = [new EmailUser()];

        $emailUser = $this->createMock(EmailUser::class);
        $email = $this->createMock(Email::class);
        $emailThread = $this->createMock(EmailThread::class);
        $emailUser->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn($email);
        $email->expects($this->exactly(2))
            ->method('getThread')
            ->willReturn($emailThread);
        $emailThread->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $emailUser->expects($this->once())
            ->method('setSeen')
            ->with(false);
        $emailUser->expects($this->exactly(2))
            ->method('isSeen')
            ->willReturn(true);
        $emailUser->expects($this->exactly(2))
            ->method('getOwner')
            ->willReturn(new User());

        $this->em->expects($this->once())
            ->method('flush');
        $this->em->expects($this->exactly(2))
            ->method('persist');

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($threadArray);

        $repository = $this->createMock(EmailUserRepository::class);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailUser')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getEmailUserByThreadId')
            ->willReturn($qb);

        $this->manager->toggleEmailUserSeen($emailUser);
    }

    public function testMarkAllEmailsAsSeenEmpty()
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $repository = $this->createMock(EmailUserRepository::class);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailUser')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findUnseenUserEmail')
            ->willReturn($qb);

        $this->manager->markAllEmailsAsSeen($user, $organization);
    }

    /**
     * @dataProvider dataSeenProvider
     */
    public function testMarkAllEmailsAsSeen(bool $isSeen, int $setSeenCalls)
    {
        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);

        $emailUser = $this->createMock(EmailUser::class);
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->willReturn($isSeen);
        $emailUser->expects($this->exactly($setSeenCalls))
            ->method('setSeen')
            ->with(true);
        $this->em->expects($this->once())
            ->method('flush');

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$emailUser]);

        $repository = $this->createMock(EmailUserRepository::class);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findUnseenUserEmail')
            ->willReturn($qb);

        $this->manager->markAllEmailsAsSeen($user, $organization);
    }

    public function testSetSeenStatus()
    {
        $user       = new User();
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
                $this->assertFalse($emailUser->isSeen());
            },
            $emailUsers
        );

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $emailUsersRepo = $this->createMock(EmailUserRepository::class);
        $emailUsersRepo->expects($this->once())
            ->method('getAllEmailUsersByEmail')
            ->with($email, $user, $organization, false)
            ->willReturn($emailUsers);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailUser')
            ->willReturn($emailUsersRepo);

        $this->manager->setSeenStatus($email, true);

        array_map(
            function (EmailUser $emailUser) {
                $this->assertTrue($emailUser->isSeen());
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
