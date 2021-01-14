<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\UserBundle\Entity\Manager\RoleManager;

class RoleManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Oro\Bundle\UserBundle\Entity\Manager\RoleManager
     */
    private $manager;

    private $em;

    private $repository;

    private $role;

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

        $this->manager = new RoleManager($this->em);
        $this->role = $this->getMockForAbstractClass('Oro\Bundle\UserBundle\Entity\Role');
    }

    public function testGetUserQueryBuilder()
    {
        $this->repository->expects($this->once())
            ->method('getUserQueryBuilder')
            ->with($this->role)
            ->will($this->returnValue(array()));

        $this->manager->getUserQueryBuilder($this->role);
    }
}
