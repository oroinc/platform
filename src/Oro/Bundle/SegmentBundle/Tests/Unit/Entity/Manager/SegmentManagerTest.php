<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class SegmentManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SegmentManager */
    protected $manager;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'getRepository'])
            ->getMock();


        $this->manager = new SegmentManager($this->em);
    }

    public function tearDown()
    {
        unset($this->em, $this->repository, $this->manager);
    }

    public function testGetSegmentTypeChoices()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();

        $this->em->expects($this->any())->method('getRepository')
            ->with('OroSegmentBundle:SegmentType')
            ->will($this->returnValue($this->repository));

        $type = new SegmentType('test');
        $type->setLabel('testLabel');

        $types = [$type];
        $this->repository->expects($this->once())->method('findAll')
            ->will($this->returnValue($types));

        $result = $this->manager->getSegmentTypeChoices();
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);

        $this->assertSame(['test' => 'testLabel'], $result);
    }

    public function testGetSegmentByEntityName()
    {
        $this->assertEmpty($this->manager->getSegmentByEntityName('test', null));

        $entityName = 'Acme\Entity\Demo';

        $segment = new Segment();
        $segment->setName('test');
        $segments = [
            $segment
        ];

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($segments));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(20)
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OroSegmentBundle:Segment'))
            ->will($this->returnValue($repo));

        $result = $this->manager->getSegmentByEntityName(
            $entityName,
            'test'
        );

        $this->assertCount(1, $result);
        $this->assertEquals('test', $result[0]['text']);
        $this->assertEquals('segment', $result[0]['type']);
    }
}
