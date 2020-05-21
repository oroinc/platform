<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\Manager;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class WindowsStateManagerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateManager */
    private $manager1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WindowsStateManager */
    private $manager2;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface */
    private $tokenStorage;

    /** @var WindowsStateManagerRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->manager1 = $this->createMock(WindowsStateManager::class);
        $this->manager2 = $this->createMock(WindowsStateManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $container = TestContainerBuilder::create()
            ->add(\stdClass::class, $this->manager1)
            ->add(AbstractUser::class, $this->manager2)
            ->getContainer($this);

        $this->registry = new WindowsStateManagerRegistry(
            [\stdClass::class, AbstractUser::class],
            $container,
            $this->tokenStorage
        );
    }

    public function testGetManagerWhenManagerFound()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new \stdClass());
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($this->manager1, $this->registry->getManager());
    }

    public function testGetManagerWhenManagerFoundByAbstractClass()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($this->manager2, $this->registry->getManager());
    }

    public function testGetManagerWhenManagerNotFound()
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

    public function testGetManagerWhenUserIsString()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn('test');
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->registry->getManager());
    }

    public function testGetManagerWhenNoToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->registry->getManager());
    }
}
