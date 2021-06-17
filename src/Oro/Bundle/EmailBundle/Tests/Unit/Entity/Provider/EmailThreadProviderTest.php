<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

class EmailThreadProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailThreadProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new EmailThreadProvider();
    }

    public function testGetEmailThreadIdFoundInThreadIdAttributes()
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getRefs')
            ->willReturn(['testMessageId']);
        $entityManager = $this->createMock(EntityManager::class);
        $repository = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $thread = $this->createMock(EmailThread::class);
        $emailFromTread = $this->createMock(Email::class);
        $emailFromTread->expects($this->exactly(2))
            ->method('getThread')
            ->willReturn($thread);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$emailFromTread]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $expressionBuilder = $this->createMock(Expr::class);
        $expressionBuilder->expects($this->atLeastOnce())
            ->method('in');
        $queryBuilder->expects($this->atLeastOnce())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $queryBuilder->expects($this->atLeastOnce())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->willReturn($repository);

        $this->assertEquals($thread, $this->provider->getEmailThread($entityManager, $email));
    }

    public function testGetEmailThreadIdFoundInXThreadIdAttributes()
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getRefs')
            ->willReturn(['testMessageId']);
        $entityManager = $this->createMock(EntityManager::class);
        $repository = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expressionBuilder = $this->createMock(Expr::class);
        $expressionBuilder->expects($this->atLeastOnce())
            ->method('in');
        $queryBuilder->expects($this->atLeastOnce())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $queryBuilder->expects($this->atLeastOnce())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);

        $thread = $this->createMock(EmailThread::class);
        $emailFromTread = $this->createMock(Email::class);
        $emailFromTread->expects($this->exactly(2))
            ->method('getThread')
            ->willReturn($thread);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$emailFromTread]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->willReturn($repository);

        $this->assertEquals($thread, $this->provider->getEmailThread($entityManager, $email));
    }

    public function testGetEmailThreadIdGenerated()
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getRefs')
            ->willReturn(['testMessageId']);
        $entityManager = $this->createMock(EntityManager::class);
        $repository = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expressionBuilder = $this->createMock(Expr::class);
        $expressionBuilder->expects($this->atLeastOnce())
            ->method('in');
        $queryBuilder->expects($this->atLeastOnce())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $queryBuilder->expects($this->atLeastOnce())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);

        $emailFromTread = $this->createMock(Email::class);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$emailFromTread]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->willReturn($repository);

        $this->assertNotEmpty($this->provider->getEmailThread($entityManager, $email));
    }

    public function testGetThreadEmailWithoutThread()
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getThread')
            ->willReturn('');
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->never())
            ->method('getRepository');

        $this->assertEquals([$email], $this->provider->getThreadEmails($entityManager, $email));
    }

    public function testGetThreadEmailWithThread()
    {
        $thread = $this->createMock(EmailThread::class);
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getThread')
            ->willReturn($thread);
        $entityManager = $this->createMock(EntityManager::class);
        $repository = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([new \stdClass(), new \stdClass()]);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->willReturn($repository);

        $this->assertCount(2, $this->provider->getThreadEmails($entityManager, $email));
    }
}
