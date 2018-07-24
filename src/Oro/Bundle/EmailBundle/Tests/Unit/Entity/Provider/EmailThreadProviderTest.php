<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

class EmailThreadProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailThreadProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new EmailThreadProvider();
    }

    public function testGetEmailThreadIdFoundInThreadIdAttributes()
    {
        $email = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue(['testMessageId']));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $thread = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $emailFromTread = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromTread->expects($this->exactly(2))
            ->method('getThread')
            ->will($this->returnValue($thread));

        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([$emailFromTread]));
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $expressionBuilder = $this->createMock(Expr::class);
        $expressionBuilder->expects($this->atLeastOnce())->method('in');
        $queryBuilder->expects($this->atLeastOnce())->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects($this->atLeastOnce())->method('where')->will($this->returnSelf());
        $queryBuilder->expects($this->atLeastOnce())->method('setParameter')->will($this->returnSelf());
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->will($this->returnValue($repository));

        $this->assertEquals($thread, $this->provider->getEmailThread($entityManager, $email));
    }

    public function testGetEmailThreadIdFoundInXThreadIdAttributes()
    {
        $email = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue(['testMessageId']));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expressionBuilder = $this->createMock(Expr::class);
        $expressionBuilder->expects($this->atLeastOnce())->method('in');
        $queryBuilder->expects($this->atLeastOnce())->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects($this->atLeastOnce())->method('where')->will($this->returnSelf());
        $queryBuilder->expects($this->atLeastOnce())->method('setParameter')->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $thread = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $emailFromTread = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromTread->expects($this->exactly(2))
            ->method('getThread')
            ->will($this->returnValue($thread));

        $query->expects($this->exactly(1))
            ->method('getResult')
            ->will($this->returnValue([$emailFromTread]));
        $queryBuilder->expects($this->exactly(1))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $repository->expects($this->exactly(1))
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->exactly(1))
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->will($this->returnValue($repository));

        $this->assertEquals($thread, $this->provider->getEmailThread($entityManager, $email));
    }

    public function testGetEmailThreadIdGenerated()
    {
        $email = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue(['testMessageId']));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $expressionBuilder = $this->createMock(Expr::class);
        $expressionBuilder->expects($this->atLeastOnce())->method('in');
        $queryBuilder->expects($this->atLeastOnce())->method('expr')->willReturn($expressionBuilder);
        $queryBuilder->expects($this->atLeastOnce())->method('where')->will($this->returnSelf());
        $queryBuilder->expects($this->atLeastOnce())->method('setParameter')->will($this->returnSelf());
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $emailFromTread = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');

        $query->expects($this->exactly(1))
            ->method('getResult')
            ->will($this->returnValue([$emailFromTread]));
        $queryBuilder->expects($this->exactly(1))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $repository->expects($this->exactly(1))
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->exactly(1))
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->will($this->returnValue($repository));

        $this->assertNotEmpty($this->provider->getEmailThread($entityManager, $email));
    }

    public function testGetThreadEmailWithoutThread()
    {
        $email = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getThread')
            ->will($this->returnValue(''));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->never())
            ->method('getRepository');

        $this->assertEquals([$email], $this->provider->getThreadEmails($entityManager, $email));
    }

    public function testGetThreadEmailWithThread()
    {
        $thread = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $email = $this->createMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getThread')
            ->will($this->returnValue($thread));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $query->expects($this->exactly(1))
            ->method('getResult')
            ->will($this->returnValue([new \stdClass(), new \stdClass()]));
        $queryBuilder->expects($this->exactly(1))
            ->method('getQuery')
            ->will($this->returnValue($query));
        $repository->expects($this->exactly(1))
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->exactly(1))
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->will($this->returnValue($repository));

        $this->assertCount(2, $this->provider->getThreadEmails($entityManager, $email));
    }
}
