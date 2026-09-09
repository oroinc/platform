<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\OAuthTokenStorage;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class OAuthTokenStorageTest extends TestCase
{
    public function testStoredCredentialsCanOnlyBeAppliedInSameProviderContext(): void
    {
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn(10);
        $organization = $this->createMock(Organization::class);
        $organization->expects(self::any())
            ->method('getId')
            ->willReturn(20);

        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $storage = new OAuthTokenStorage($requestStack, $tokenAccessor);
        $handle = $storage->saveAccessTokenAndReturnHandleCode('gmail', 'access-token', 'refresh-token', 3600);

        self::assertEquals(64, strlen($handle));

        $origin = new UserEmailOrigin();
        self::assertFalse($storage->applyToOrigin($handle, 'microsoft', $origin));
        self::assertNull($origin->getAccessToken());

        self::assertTrue($storage->applyToOrigin($handle, 'gmail', $origin));
        self::assertEquals('access-token', $origin->getAccessToken());
        self::assertEquals('refresh-token', $origin->getRefreshToken());
        self::assertGreaterThan(new \DateTime('+59 minutes'), $origin->getAccessTokenExpiresAt());
    }

    public function testStoredCredentialsCannotBeAppliedByAnotherUser(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $organization = $this->createOrganization(20);
        $storage = new OAuthTokenStorage(
            $requestStack,
            $this->createTokenAccessor($this->createUser(10), $organization)
        );
        $handle = $storage->saveAccessTokenAndReturnHandleCode('gmail', 'access-token', 'refresh-token', 3600);

        $anotherUserStorage = new OAuthTokenStorage(
            $requestStack,
            $this->createTokenAccessor($this->createUser(11), $organization)
        );

        self::assertFalse($anotherUserStorage->applyToOrigin($handle, 'gmail', new UserEmailOrigin()));
    }

    public function testStoredCredentialsCannotBeAppliedInAnotherOrganization(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $user = $this->createUser(10);
        $storage = new OAuthTokenStorage(
            $requestStack,
            $this->createTokenAccessor($user, $this->createOrganization(20))
        );
        $handle = $storage->saveAccessTokenAndReturnHandleCode('gmail', 'access-token', 'refresh-token', 3600);

        $anotherOrganizationStorage = new OAuthTokenStorage(
            $requestStack,
            $this->createTokenAccessor($user, $this->createOrganization(21))
        );

        self::assertFalse($anotherOrganizationStorage->applyToOrigin($handle, 'gmail', new UserEmailOrigin()));
    }

    private function createTokenAccessor(User $user, Organization $organization): TokenAccessorInterface
    {
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        return $tokenAccessor;
    }

    private function createUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $user;
    }

    private function createOrganization(int $id): Organization
    {
        $organization = $this->createMock(Organization::class);
        $organization->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $organization;
    }
}
