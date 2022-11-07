<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException;
use Oro\Bundle\SSOBundle\Security\Core\Exception\ResourceOwnerNotAllowedException;
use Oro\Bundle\SSOBundle\Security\Core\User\OAuthUserProvider;
use Oro\Bundle\SSOBundle\Security\Core\User\OAuthUserProviderInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class OAuthUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OAuthUserProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userProvider;

    /** @var UserCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userChecker;

    /** @var OAuthUserProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->userProvider = $this->createMock(OAuthUserProviderInterface::class);
        $this->userChecker = $this->createMock(UserCheckerInterface::class);

        $userProviders = TestContainerBuilder::create()
            ->add('test_resource_owner', $this->userProvider)
            ->getContainer($this);

        $this->provider = new OAuthUserProvider($userProviders, $this->userChecker);
    }

    private function getUserResponse(
        string $username = 'username',
        string $email = 'username@example.com',
        string $resourceOwner = 'test_resource_owner'
    ): UserResponseInterface {
        $userResponse = $this->createMock(UserResponseInterface::class);
        $userResponse->expects(self::any())
            ->method('getUsername')
            ->willReturn($username);
        $userResponse->expects(self::any())
            ->method('getEmail')
            ->willReturn($email);

        $resourceOwnerInstance = $this->createMock(ResourceOwnerInterface::class);
        $userResponse->expects(self::any())
            ->method('getResourceOwner')
            ->willReturn($resourceOwnerInstance);
        $resourceOwnerInstance->expects(self::any())
            ->method('getName')
            ->willReturn($resourceOwner);

        return $userResponse;
    }

    public function testShouldThrowExceptionIfUserProviderNotFound()
    {
        $this->expectException(ResourceOwnerNotAllowedException::class);
        $this->expectExceptionMessage('SSO is not supported.');

        $this->provider->loadUserByOAuthUserResponse(
            $this->getUserResponse('username', 'username@example.com', 'unknown_resource_owner')
        );
    }

    public function testShouldThrowExceptionIfSsoIsDisabled()
    {
        $this->expectException(ResourceOwnerNotAllowedException::class);
        $this->expectExceptionMessage('SSO is not enabled.');

        $this->userProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->provider->loadUserByOAuthUserResponse($this->getUserResponse());
    }

    public function testShouldReturnUserByOAuthIdWhenUserFound()
    {
        $user = new User();
        $user->addUserRole(new Role());

        $userResponse = $this->getUserResponse();

        $this->userProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->userProvider->expects(self::once())
            ->method('getAllowedDomains')
            ->willReturn([]);
        $this->userProvider->expects(self::once())
            ->method('findUser')
            ->with(self::identicalTo($userResponse))
            ->willReturn($user);

        $loadedUser = $this->provider->loadUserByOAuthUserResponse($userResponse);
        self::assertSame($user, $loadedUser);
    }

    public function testShouldReturnUserByOAuthIdWhenUserFoundAndEmailIsAllowed()
    {
        $user = new User();
        $user->addUserRole(new Role());

        $userResponse = $this->getUserResponse();

        $this->userProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->userProvider->expects(self::once())
            ->method('getAllowedDomains')
            ->willReturn(['example.com']);
        $this->userProvider->expects(self::once())
            ->method('findUser')
            ->with(self::identicalTo($userResponse))
            ->willReturn($user);

        $loadedUser = $this->provider->loadUserByOAuthUserResponse($userResponse);
        self::assertSame($user, $loadedUser);
    }

    public function testShouldThrowExceptionWhenEmailIsNotAllowed()
    {
        $this->expectException(EmailDomainNotAllowedException::class);
        $this->expectExceptionMessage('The user email is not allowed.');

        $this->userProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->userProvider->expects(self::once())
            ->method('getAllowedDomains')
            ->willReturn(['another.com']);
        $this->userProvider->expects(self::never())
            ->method('findUser');

        $this->provider->loadUserByOAuthUserResponse($this->getUserResponse());
    }

    public function testShouldThrowExceptionIfUserIsDisabled()
    {
        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('Account is locked.');

        $user = new User();
        $user->addUserRole(new Role());
        $user->setEnabled(false);

        $userResponse = $this->getUserResponse();

        $this->userProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->userProvider->expects(self::once())
            ->method('getAllowedDomains')
            ->willReturn([]);
        $this->userProvider->expects(self::once())
            ->method('findUser')
            ->with(self::identicalTo($userResponse))
            ->willReturn($user);

        $exception = new LockedException('Account is locked.');
        $exception->setUser($user);

        $this->userChecker->expects(self::once())
            ->method('checkPreAuth')
            ->with($user)
            ->willThrowException($exception);

        $this->provider->loadUserByOAuthUserResponse($userResponse);
    }

    public function testShouldThrowExceptionIfUserNotFound()
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('The user does not exist.');

        $userResponse = $this->getUserResponse();

        $this->userProvider->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->userProvider->expects(self::once())
            ->method('getAllowedDomains')
            ->willReturn([]);
        $this->userProvider->expects(self::once())
            ->method('findUser')
            ->with(self::identicalTo($userResponse))
            ->willReturn(null);

        $this->provider->loadUserByOAuthUserResponse($userResponse);
    }
}
