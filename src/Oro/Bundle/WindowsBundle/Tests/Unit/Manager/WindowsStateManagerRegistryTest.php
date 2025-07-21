<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\Manager;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WindowsStateManagerRegistryTest extends TestCase
{
    private WindowsStateManager&MockObject $manager1;
    private WindowsStateManager&MockObject $manager2;
    private TokenStorageInterface&MockObject $tokenStorage;
    private WindowsStateManagerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager1 = $this->createMock(WindowsStateManager::class);
        $this->manager2 = $this->createMock(WindowsStateManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $container = TestContainerBuilder::create()
            ->add(User::class, $this->manager1)
            ->add(AbstractUser::class, $this->manager2)
            ->getContainer($this);

        $this->registry = new WindowsStateManagerRegistry(
            [User::class, AbstractUser::class],
            $container,
            $this->tokenStorage
        );
    }

    public function testGetManagerWhenManagerFound(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($this->manager1, $this->registry->getManager());
    }

    public function testGetManagerWhenManagerFoundByAbstractClass(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(AbstractUser::class));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($this->manager2, $this->registry->getManager());
    }

    public function testGetManagerWhenManagerNotFound(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->registry->getManager());
    }

    public function testGetManagerWhenUserIsString(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->registry->getManager());
    }

    public function testGetManagerWhenNoToken(): void
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->registry->getManager());
    }
}
