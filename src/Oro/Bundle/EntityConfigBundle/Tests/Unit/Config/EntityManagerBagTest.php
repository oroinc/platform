<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;

class EntityManagerBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var EntityManagerBag */
    protected $entityManagerBag;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManagerBag = new EntityManagerBag($this->doctrine);
    }

    public function testGetEntityManagersWithoutAdditionalEntityManagers()
    {
        $defaultEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with(null)
            ->willReturn($defaultEm);

        $result = $this->entityManagerBag->getEntityManagers();
        $this->assertCount(1, $result);
        $this->assertSame($defaultEm, $result[0]);
    }

    public function testGetEntityManagers()
    {
        $defaultEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $anotherEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->at(0))
            ->method('getManager')
            ->with(null)
            ->willReturn($defaultEm);
        $this->doctrine->expects($this->at(1))
            ->method('getManager')
            ->with('another')
            ->willReturn($anotherEm);

        $this->entityManagerBag->addEntityManager('another');

        $result = $this->entityManagerBag->getEntityManagers();
        $this->assertCount(2, $result);
        $this->assertSame($defaultEm, $result[0]);
        $this->assertSame($anotherEm, $result[1]);
    }
}
