<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\UserBundle\Entity\Manager\StatusManager;
use Oro\Bundle\UserBundle\Entity\Status;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class StatusManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $um;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var StatusManager */
    private $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->um = $this->createMock(UserManager::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->manager = new StatusManager($this->em, $this->um);
    }

    public function testGetUserStatuses()
    {
        $user = $this->createMock(User::class);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([]);

        $this->manager->getUserStatuses($user);
    }

    public function testDeleteStatusWhenUserStatusDoesNotEqualToDeletingStatus()
    {
        $user = $this->createMock(User::class);
        $status = new Status();

        $user->expects($this->never())
            ->method('setCurrentStatus');

        $this->assertFalse($this->manager->deleteStatus($user, $status, true));
    }

    public function testDeleteStatus()
    {
        $user = new User();
        $status = new Status();
        $status->setUser($user);
        $user->setCurrentStatus($status);

        $this->um->expects($this->once())
            ->method('updateUser');
        $this->um->expects($this->once())
            ->method('reloadUser');

        $this->em->expects($this->once())
            ->method('remove');
        $this->em->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->manager->deleteStatus($user, $status, true));

        $this->assertNull($user->getCurrentStatus());
    }

    public function testSetCurrentStatus()
    {
        $user = $this->createMock(User::class);
        $status = new Status();

        $user->expects($this->once())
            ->method('setCurrentStatus')
            ->with($this->identicalTo($status));
        $this->um->expects($this->once())
            ->method('updateUser')
            ->with($this->identicalTo($user));
        $this->um->expects($this->once())
            ->method('reloadUser')
            ->with($this->identicalTo($user));

        $this->manager->setCurrentStatus($user, $status, true);
    }
}
