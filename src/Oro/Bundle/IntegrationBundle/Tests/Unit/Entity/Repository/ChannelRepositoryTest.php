<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

class ChannelRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChannelRepository */
    protected $repository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->repository = new ChannelRepository(
            $this->entityManager,
            new ClassMetadata('Oro\Bundle\IntegrationBundle\Entity\Channel')
        );
    }

    public function tearDown()
    {
        unset($this->entityManager, $this->repository);
    }

    public function testGetConfiguredChannelsForSync($type = null)
    {
        $expectedResult = [];

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('getResult')
            ->will($this->returnValue($expectedResult));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('select')->with('c')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('from')->with('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('where')->with('c.transport is NOT NULL')
            ->will($this->returnSelf());

        if (null !== $type) {
            $qb->expects($this->once())
                ->method('where')->with('c.type = :type')
                ->will($this->returnSelf());
            $qb->expects($this->once())
                ->method('setParameter')->with('type', $type)
                ->will($this->returnSelf());
        }

        $qb->expects($this->once())->method('getQuery')
            ->will($this->returnValue($query));

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')->with()
            ->will($this->returnValue($qb));

        $result = $this->repository->getConfiguredChannelsForSync($type);
        $this->assertSame($expectedResult, $result);
    }
}
