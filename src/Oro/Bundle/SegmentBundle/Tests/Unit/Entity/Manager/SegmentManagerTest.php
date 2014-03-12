<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
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
            ->disableOriginalConstructor()->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();

        $this->em->expects($this->any())->method('getRepository')->with('OroSegmentBundle:SegmentType')
            ->will($this->returnValue($this->repository));


        $this->manager = new SegmentManager($this->em);
    }

    public function tearDown()
    {
        unset($this->em, $this->repository, $this->manager);
    }

    public function testGetSegmentTypeChoices()
    {
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
}
