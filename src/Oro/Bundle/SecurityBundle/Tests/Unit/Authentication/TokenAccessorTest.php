<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var TokenAccessor */
    private $tokenAccessor;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->tokenAccessor = new TokenAccessor($this->tokenStorage);
    }

    public function testGetToken()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame($token, $this->tokenAccessor->getToken());
    }

    public function testGetNullToken()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getToken());
    }

    public function testSetToken()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::identicalTo($token));

        $this->tokenAccessor->setToken($token);
    }

    public function testSetNullToken()
    {
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::isNull());

        $this->tokenAccessor->setToken(null);
    }

    public function testHasUser()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(AbstractUser::class));

        self::assertTrue($this->tokenAccessor->hasUser());
    }

    public function testHasUserWhenTokenDoesNotContainUser()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        self::assertFalse($this->tokenAccessor->hasUser());
    }

    public function testHasUserWhenTokenDoesNotExist()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertFalse($this->tokenAccessor->hasUser());
    }

    public function testGetUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(AbstractUser::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        self::assertSame($user, $this->tokenAccessor->getUser());
    }

    public function testGetUserWhenTokenDoesNotContainUser()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getUser());
    }

    public function testGetUserWhenTokenDoesNotExist()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getUser());
    }

    public function testGetUserId()
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(AbstractUser::class);
        $userId = 123;

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getId')
            ->willReturn($userId);


        self::assertSame($userId, $this->tokenAccessor->getUserId());
    }

    public function testGetUserIdWhenTokenDoesNotContainUser()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getUserId());
    }

    public function testGetUserIdWhenTokenDoesNotExist()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getUserId());
    }

    public function testGetOrganization()
    {
        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $organization = $this->createMock(Organization::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganizationContext')
            ->willReturn($organization);

        self::assertSame($organization, $this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationWhenTokenDoesNotContainOrganization()
    {
        $token = $this->createMock(OrganizationContextTokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganizationContext')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationWhenTokenDoesNotSupportOrganization()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull($this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationWhenTokenDoesNotExist()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationId()
    {
        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $organization = $this->createMock(Organization::class);
        $organizationId = 123;

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganizationContext')
            ->willReturn($organization);
        $organization->expects(self::once())
            ->method('getId')
            ->willReturn($organizationId);


        self::assertSame($organizationId, $this->tokenAccessor->getOrganizationId());
    }

    public function testGetOrganizationIdWhenTokenDoesNotContainOrganization()
    {
        $token = $this->createMock(OrganizationContextTokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganizationContext')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganizationId());
    }

    public function testGetOrganizationIdWhenTokenDoesNotSupportOrganization()
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull($this->tokenAccessor->getOrganizationId());
    }

    public function testGetOrganizationIdWhenTokenDoesNotExist()
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganizationId());
    }
}
