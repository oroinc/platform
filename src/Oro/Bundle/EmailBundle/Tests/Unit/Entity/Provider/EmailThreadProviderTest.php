<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;

class EmailThreadProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailThreadProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new EmailThreadProvider();
    }

    public function testGetEmailThreadIdFoundInThreadIdAttributes()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue('testMessageId'));
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

        $treadId = 'testTreadId';
        $emailFromTread = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromTread->expects($this->exactly(2))
            ->method('getThreadId')
            ->will($this->returnValue($treadId));

        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([$emailFromTread]));
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->will($this->returnValue($repository));

        $this->assertEquals($treadId, $this->provider->getEmailThreadId($entityManager, $email));
    }

    public function testGetEmailThreadIdFoundInXThreadIdAttributes()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue('testMessageId'));
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

        $treadId = 'testXTreadId';
        $emailFromTread = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromTread->expects($this->exactly(2))
            ->method('getXThreadId')
            ->will($this->returnValue($treadId));

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

        $this->assertEquals($treadId, $this->provider->getEmailThreadId($entityManager, $email));
    }

    public function testGetEmailThreadIdFoundInOwnAttributes()
    {
        $treadId = 'testXTreadId';
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue(null));
        $email->expects($this->exactly(2))
            ->method('getXThreadId')
            ->will($this->returnValue($treadId));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assertEquals($treadId, $this->provider->getEmailThreadId($entityManager, $email));
    }

    public function testGetEmailThreadIdGenerated()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getRefs')
            ->will($this->returnValue('testMessageId'));
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

        $emailFromTread = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromTread->expects($this->exactly(1))
            ->method('getXThreadId')
            ->will($this->returnValue(null));

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

        $this->assertNotEmpty($this->provider->getEmailThreadId($entityManager, $email));
    }

    public function testGetThreadEmailWithoutThread()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getThreadId')
            ->will($this->returnValue(''));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->never())
            ->method('getRepository');

        $this->assertEquals([], $this->provider->getThreadEmails($entityManager, $email));
    }

    public function testGetThreadEmailWithThread()
    {
        $treadId = 'testTreadId';
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getThreadId')
            ->will($this->returnValue($treadId));
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
