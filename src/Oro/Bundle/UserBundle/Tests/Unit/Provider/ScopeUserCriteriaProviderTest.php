<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ScopeUserCriteriaProviderTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private ScopeUserCriteriaProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new ScopeUserCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaField(): void
    {
        $this->assertEquals(ScopeUserCriteriaProvider::USER, $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValue(): void
    {
        $user = new User();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertSame($user, $this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueForNotSupportedUser(): void
    {
        $user = $this->createMock(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueWithoutToken(): void
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueWithoutUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueType(): void
    {
        $this->assertEquals(User::class, $this->provider->getCriteriaValueType());
    }
}
