<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity\Repository;

use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;

class DashboardRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DashboardRepository
     */
    protected $repository;

    /**
     * @var DashboardRepository
     */
    protected $qb;

    protected function setUp()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $this->qb->expects($this->any())->method('select')->will($this->returnValue($this->qb));
        $this->qb->expects($this->any())->method('from')->will($this->returnValue($this->qb));
        $this->qb->expects($this->any())->method('where')->will($this->returnValue($this->qb));
        $entityManager->expects($this->once())->method('createQueryBuilder')->will($this->returnValue($this->qb));
        $this->repository = new DashboardRepository($entityManager, $meta);
    }

    public function testGetAvailableDashboards()
    {
        $acl = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMock('StdClass', array('execute'));
        $query->expects($this->any())->method('execute');
        $acl->expects($this->once())->method('apply')->with($this->qb)->will($this->returnValue($query));
        $this->repository->setAclHelper($acl);

        $this->repository->getAvailableDashboards();
    }

    public function testGetAvailableDashboard()
    {
        $id = 42;
        $acl = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMock('StdClass', array('getOneOrNullResult', 'setParameters'));
        $query->expects($this->once())->method('getOneOrNullResult');
        $query->expects($this->once())
            ->method('setParameters')
            ->with(array('id' => $id))
            ->will($this->returnValue($query));
        $acl->expects($this->once())->method('apply')->with($this->qb)->will($this->returnValue($query));
        $this->repository->setAclHelper($acl);

        $this->repository->getAvailableDashboard($id);
    }
}
