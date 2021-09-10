<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Manager\GroupManager;

class GroupManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Oro\Bundle\UserBundle\Entity\Manager\GroupManager */
    private $manager;

    private $em;

    private $repository;

    private $group;

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

        $this->manager = new GroupManager($this->em);
        $this->group = $this->createMock(Group::class);
    }

    public function testGetUserQueryBuilder()
    {
        $this->repository->expects($this->once())
            ->method('getUserQueryBuilder')
            ->with($this->group)
            ->willReturn([]);

        $this->manager->getUserQueryBuilder($this->group);
    }
}
