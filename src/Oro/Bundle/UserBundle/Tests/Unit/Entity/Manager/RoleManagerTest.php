<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\UserBundle\Entity\Manager\RoleManager;
use Oro\Bundle\UserBundle\Entity\Role;

class RoleManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Oro\Bundle\UserBundle\Entity\Manager\RoleManager */
    private $manager;

    private $em;

    private $repository;

    private $role;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);

        $this->repository = $this->getMockBuilder(\Doctrine\Persistence\ObjectRepository::class)
            ->onlyMethods(['find', 'findAll', 'findBy', 'findOneBy', 'getClassName'])
            ->addMethods(['getUserQueryBuilder'])
            ->getMock();

        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->manager = new RoleManager($this->em);
        $this->role = $this->createMock(Role::class);
    }

    public function testGetUserQueryBuilder()
    {
        $this->repository->expects($this->once())
            ->method('getUserQueryBuilder')
            ->with($this->role)
            ->willReturn([]);

        $this->manager->getUserQueryBuilder($this->role);
    }
}
