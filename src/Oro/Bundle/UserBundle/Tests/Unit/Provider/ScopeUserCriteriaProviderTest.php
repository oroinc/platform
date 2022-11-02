<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\ScopeUserCriteriaProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ScopeUserCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ScopeUserCriteriaProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->provider = new ScopeUserCriteriaProvider($this->tokenStorage);
    }

    public function testGetCriteriaField()
    {
        $this->assertEquals(ScopeUserCriteriaProvider::USER, $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValue()
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

    public function testGetCriteriaValueForNotSupportedUser()
    {
        $user = new \stdClass();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueWithoutToken()
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->assertNull($this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueWithoutUser()
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

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(User::class, $this->provider->getCriteriaValueType());
    }
}
