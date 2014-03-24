<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentSnapshotRepository;

class SegmentSnapshotRepositoryTest extends SegmentDefinitionTestCase
{
    const ENTITY_NAME = 'OroSegmentBundle:SegmentSnapshot';

    /** @var SegmentSnapshotRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->setMethods(['createQueryBuilder', 'beginTransaction', 'commit'])
            ->getMock();

        $this->repository = new SegmentSnapshotRepository(
            $this->em,
            new ClassMetadata('OroSegmentBundle:SegmentSnapshot')
        );
    }

    public function tearDown()
    {
        unset($this->em, $this->repository);
    }

    public function testRemoveBySegment()
    {
        $segment = $this->getSegment();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['delete', 'where', 'setParameter', 'getQuery'])
            ->getMock();
        $queryBuilder->expects($this->once())->method('delete')->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('where')->with('snp.segment = :segment')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')->with('segment', $segment)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('getQuery')
            ->will($this->returnValue($query));

        $this->em->expects($this->once())->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $this->repository->removeBySegment($segment);
    }

    public function testGetIdentifiersSelectQueryBuilder()
    {
        $segment = $this->getSegment();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['select', 'from', 'where', 'setParameter'])
            ->getMock();
        $queryBuilder->expects($this->at(0))->method('select')->with('snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('from')->with(self::ENTITY_NAME, 'snp')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))->method('select')->with('snp.entityId')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('where')->with('snp.segment = :segment')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())->method('setParameter')->with('segment', $segment)
            ->will($this->returnSelf());

        $this->em->expects($this->once())->method('createQueryBuilder')
            ->will($this->returnValue($queryBuilder));

        $result = $this->repository->getIdentifiersSelectQueryBuilder($segment);
        $this->assertSame($queryBuilder, $result);
    }
}
