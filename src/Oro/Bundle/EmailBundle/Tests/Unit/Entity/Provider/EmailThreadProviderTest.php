<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\UserBundle\Entity\User;

class EmailThreadProviderTest extends \PHPUnit\Framework\TestCase
{
    private EmailThreadProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new EmailThreadProvider();
    }

    public function testGetEmailReferencesWithoutThread(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getRefs')
            ->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getEmailReferences($entityManager, $email));
    }

    public function testGetEmailReferencesWithThread(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getRefs')
            ->willReturn(['ref1']);
        $result = [new Email(), new Email()];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Email::class, 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.messageId IN (:messagesIds)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('messagesIds', ['ref1'])
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        self::assertSame($result, $this->provider->getEmailReferences($entityManager, $email));
    }

    public function testGetHeadEmailWithoutThread(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())
            ->method(self::anything());

        self::assertEquals($email, $this->provider->getHeadEmail($entityManager, $email));
    }

    public function testGetHeadEmailWithThreadAndNoThreadEmails(): void
    {
        $thread = $this->createMock(EmailThread::class);
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn($thread);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Email::class, 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.thread = :thread')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('thread', $thread)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('e.sentAt', 'DESC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        self::assertSame($email, $this->provider->getHeadEmail($entityManager, $email));
    }

    public function testGetHeadEmailWithThread(): void
    {
        $thread = $this->createMock(EmailThread::class);
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn($thread);
        $threadEmail = new Email();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Email::class, 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.thread = :thread')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('thread', $thread)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('e.sentAt', 'DESC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($threadEmail);

        self::assertSame($threadEmail, $this->provider->getHeadEmail($entityManager, $email));
    }

    public function testGetThreadEmailsWithoutThread(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())
            ->method(self::anything());

        self::assertEquals([$email], $this->provider->getThreadEmails($entityManager, $email));
    }

    public function testGetThreadEmailsWithThread(): void
    {
        $thread = $this->createMock(EmailThread::class);
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn($thread);
        $result = [new Email(), new Email()];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Email::class, 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.thread = :thread')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('thread', $thread)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('e.sentAt', 'DESC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        self::assertSame($result, $this->provider->getThreadEmails($entityManager, $email));
    }

    public function testGetUserThreadEmailsWithoutThread(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn(null);
        $user = $this->createMock(User::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())
            ->method(self::anything());

        self::assertEquals([$email], $this->provider->getUserThreadEmails($entityManager, $email, $user));
    }

    public function testGetUserThreadEmailsWithThread(): void
    {
        $thread = $this->createMock(EmailThread::class);
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn($thread);
        $user = $this->createMock(User::class);
        $result = [new Email(), new Email()];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Email::class, 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('innerJoin')
            ->with('e.emailUsers', 'eu')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.thread = :thread')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('eu.owner = :user')
            ->willReturnSelf();
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['thread', $thread], ['user', $user])
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('e.sentAt', 'DESC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        self::assertSame($result, $this->provider->getUserThreadEmails($entityManager, $email, $user));
    }

    public function testGetUserThreadEmailsWithThreadAndMailboxes(): void
    {
        $thread = $this->createMock(EmailThread::class);
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getThread')
            ->willReturn($thread);
        $user = $this->createMock(User::class);
        $mailboxes = [new Mailbox()];
        $result = [new Email(), new Email()];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('from')
            ->with(Email::class, 'e')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('innerJoin')
            ->with('e.emailUsers', 'eu')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.thread = :thread')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('eu.mailboxOwner IN (:mailboxes) OR eu.owner = :user')
            ->willReturnSelf();
        $queryBuilder->expects(self::exactly(3))
            ->method('setParameter')
            ->withConsecutive(['thread', $thread], ['user', $user], ['mailboxes', $mailboxes])
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('orderBy')
            ->with('e.sentAt', 'DESC')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($result);

        self::assertSame($result, $this->provider->getUserThreadEmails($entityManager, $email, $user, $mailboxes));
    }
}
