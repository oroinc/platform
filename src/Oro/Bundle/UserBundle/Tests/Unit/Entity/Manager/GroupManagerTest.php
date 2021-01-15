<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\UserBundle\Entity\Manager\GroupManager;

class GroupManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Oro\Bundle\UserBundle\Entity\Manager\GroupManager
     */
    private $manager;

    private $em;

    private $repository;

    private $group;

    protected function setUp(): void
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(\Doctrine\Persistence\ObjectRepository::class)
            ->onlyMethods(['find', 'findAll', 'findBy', 'findOneBy', 'getClassName'])
            ->addMethods(['getUserQueryBuilder'])
            ->getMock();

        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->manager = new GroupManager($this->em);
        $this->group = $this->getMockForAbstractClass('Oro\Bundle\UserBundle\Entity\Group');
    }

    public function testGetUserQueryBuilder()
    {
        $this->repository->expects($this->once())
            ->method('getUserQueryBuilder')
            ->with($this->group)
            ->will($this->returnValue(array()));

        $this->manager->getUserQueryBuilder($this->group);
    }
}
