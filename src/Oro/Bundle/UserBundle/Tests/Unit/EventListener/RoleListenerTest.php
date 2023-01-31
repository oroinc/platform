<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclSidManager;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\EventListener\RoleListener;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\RoleStub;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class RoleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclSidManager|\PHPUnit\Framework\MockObject\MockObject */
    private $aclSidManager;

    /** @var RoleListener */
    private $listener;

    protected function setUp(): void
    {
        $this->aclSidManager = $this->createMock(AclSidManager::class);

        $this->listener = new RoleListener($this->aclSidManager);
    }

    public function testPreUpdate()
    {
        $role = new RoleStub();
        $sid = $this->createMock(SecurityIdentityInterface::class);

        $this->aclSidManager->expects($this->once())
            ->method('getSid')
            ->with('new')
            ->willReturn($sid);
        $this->aclSidManager->expects($this->once())
            ->method('updateSid')
            ->with($this->identicalTo($sid), 'old');

        $this->listener->preUpdate(
            $role,
            $this->getPreUpdateEvent($role, ['role' => ['old', 'new']])
        );
    }

    public function testPreUpdateWhenRoleFieldNotChanged()
    {
        $role = new RoleStub();

        $this->aclSidManager->expects($this->never())
            ->method($this->anything());

        $this->listener->preUpdate(
            $role,
            $this->getPreUpdateEvent($role, ['another' => ['old', 'new']])
        );
    }

    private function getPreUpdateEvent(AbstractRole $role, array $changeSet): PreUpdateEventArgs
    {
        return new PreUpdateEventArgs($role, $this->createMock(EntityManagerInterface::class), $changeSet);
    }

    public function testPrePersistWhenRoleFieldIsNotEmpty()
    {
        $roleName = 'ROLE_123';
        $role = new RoleStub();
        $role->setRole($roleName, false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('getRepository');

        $this->listener->prePersist($role, new LifecycleEventArgs($role, $em));

        $this->assertEquals($roleName, $role->getRole());
    }

    public function testPrePersistWhenRoleFieldIsEmpty()
    {
        $role = new RoleStub();

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ObjectRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->listener->prePersist($role, new LifecycleEventArgs($role, $em));

        $this->assertNotEmpty($role->getRole());
    }

    public function testPrePersistWhenRoleFieldIsEmptyAndWhenFirstAttemptToGenerateUniqueRoleNameFailed()
    {
        $role = new RoleStub();

        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ObjectRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($repository);
        $repository->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(new RoleStub(), null);

        $this->listener->prePersist($role, new LifecycleEventArgs($role, $em));

        $this->assertNotEmpty($role->getRole());
    }

    public function testPrePersistWhenRoleFieldIsEmptyAndWhenAttemptsToGenerateUniqueRoleNameExceeded()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('10 attempts to generate unique role are failed.');

        $role = new RoleStub();
        $em = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(ObjectRepository::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Role::class)
            ->willReturn($repository);
        $repository->expects($this->exactly(10))
            ->method('findOneBy')
            ->willReturn(new RoleStub());

        $this->listener->prePersist($role, new LifecycleEventArgs($role, $em));
    }
}
