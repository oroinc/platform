<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TokenAccessorTest extends TestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private TokenAccessor $tokenAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->tokenAccessor = new TokenAccessor($this->tokenStorage);
    }

    public function testGetToken(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertSame($token, $this->tokenAccessor->getToken());
    }

    public function testGetNullToken(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getToken());
    }

    public function testSetToken(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::identicalTo($token));

        $this->tokenAccessor->setToken($token);
    }

    public function testSetNullToken(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::isNull());

        $this->tokenAccessor->setToken(null);
    }

    public function testHasUser(): void
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

    public function testHasUserWhenTokenDoesNotContainUser(): void
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

    public function testHasUserWhenTokenDoesNotExist(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertFalse($this->tokenAccessor->hasUser());
    }

    public function testGetUser(): void
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

    public function testGetUserWhenTokenDoesNotContainUser(): void
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

    public function testGetUserWhenTokenDoesNotExist(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getUser());
    }

    public function testGetUserId(): void
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

    public function testGetUserIdWhenTokenDoesNotContainUser(): void
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

    public function testGetUserIdWhenTokenDoesNotExist(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getUserId());
    }

    public function testGetOrganization(): void
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $organization = $this->createMock(Organization::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        self::assertSame($organization, $this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationWhenTokenDoesNotContainOrganization(): void
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationWhenTokenDoesNotSupportOrganization(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull($this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationWhenTokenDoesNotExist(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganization());
    }

    public function testGetOrganizationId(): void
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $organization = $this->createMock(Organization::class);
        $organizationId = 123;

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);
        $organization->expects(self::once())
            ->method('getId')
            ->willReturn($organizationId);

        self::assertSame($organizationId, $this->tokenAccessor->getOrganizationId());
    }

    public function testGetOrganizationIdWhenTokenDoesNotContainOrganization(): void
    {
        $token = $this->createMock(OrganizationAwareTokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganizationId());
    }

    public function testGetOrganizationIdWhenTokenDoesNotSupportOrganization(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        self::assertNull($this->tokenAccessor->getOrganizationId());
    }

    public function testGetOrganizationIdWhenTokenDoesNotExist(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        self::assertNull($this->tokenAccessor->getOrganizationId());
    }
}
