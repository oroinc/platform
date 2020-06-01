<?php

namespace Oro\Bundle\SSOBundle\Tests\Entity;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SSOBundle\Security\Core\User\OAuthUserProvider;
use Oro\Bundle\SSOBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;

class OAuthUserProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $cm;

    /** @var OAuthUserProvider */
    private $oauthProvider;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->cm = $this->createMock(ConfigManager::class);

        $this->oauthProvider = new OAuthUserProvider($this->userManager, $this->cm);
    }

    public function testLoadUserByOAuthUserResponseShouldThrowExceptionIfSSOIsDisabled()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SSO is not enabled');

        $this->cm
            ->expects($this->any())
            ->method('get')
            ->with('oro_sso.enable_google_sso')
            ->willReturn(false);

        $userResponse = $this->createMock(UserResponseInterface::class);

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }

    public function testLoadUserByOAuthShouldReturnUserByOauthIdIfFound()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_sso.enable_google_sso')
            ->willReturn(true);

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_sso.domains')
            ->willReturn([]);

        $userResponse = $this->createMock(UserResponseInterface::class);
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('username');

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->willReturn($this->createMock(ResourceOwnerInterface::class));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->willReturn('username@example.com');

        $user = new TestingUser();
        $user->addRole(new Role());

        $this->userManager
            ->expects($this->once())
            ->method('findUserBy')
            ->with(['Id' => 'username'])
            ->willReturn($user);

        $loadedUser = $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldReturnExceptionIfUserIsDisabled()
    {
        $this->expectException(\Symfony\Component\Security\Core\Exception\DisabledException::class);
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_sso.enable_google_sso')
            ->willReturn(true);

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_sso.domains')
            ->willReturn([]);

        $userResponse = $this->createMock(UserResponseInterface::class);
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('username');

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->willReturn($this->createMock(ResourceOwnerInterface::class));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->willReturn('username@example.com');

        $user = new TestingUser();
        $user->addRole(new Role());
        $user->setEnabled(false);

        $this->userManager
            ->expects($this->once())
            ->method('findUserBy')
            ->with(['Id' => 'username'])
            ->willReturn($user);

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }

    public function testLoadUserByOAuthShouldToFindUserByEmailIfLoadingByOauthIdFails()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_sso.enable_google_sso')
            ->willReturn(true);

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_sso.domains')
            ->willReturn([]);

        $userResponse = $this->createMock(UserResponseInterface::class);
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('username');

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->willReturn($this->createMock(ResourceOwnerInterface::class));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->willReturn('username@example.com');

        $this->userManager
            ->expects($this->at(0))
            ->method('findUserBy')
            ->with(['Id' => 'username']);

        $user = new TestingUser();
        $user->addRole(new Role());

        $this->userManager
            ->expects($this->at(1))
            ->method('findUserByEmail')
            ->with('username@example.com')
            ->willReturn($user);

        $loadedUser = $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldFindUserByEmailWithRestrictedEmailDomainIfLoadingByOauthIdFails()
    {
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_sso.enable_google_sso')
            ->willReturn(true);

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_sso.domains')
            ->willReturn(['example.com']);

        $userResponse = $this->createMock(UserResponseInterface::class);
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('username');

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->willReturn($this->createMock(ResourceOwnerInterface::class));

        $userResponse
            ->expects($this->any())
            ->method('getEmail')
            ->willReturn('username@example.com');

        $this->userManager
            ->expects($this->at(0))
            ->method('findUserBy')
            ->with(['Id' => 'username']);

        $user = new TestingUser();
        $user->addRole(new Role());

        $this->userManager
            ->expects($this->at(1))
            ->method('findUserByEmail')
            ->with('username@example.com')
            ->willReturn($user);

        $loadedUser = $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
        $this->assertSame($user, $loadedUser);
    }

    public function testLoadUserByOAuthShouldThrowExceptionIfEmailDomainIsDisabled()
    {
        $this->expectException(\Oro\Bundle\SSOBundle\Security\Core\Exception\EmailDomainNotAllowedException::class);
        $this->cm
            ->expects($this->at(0))
            ->method('get')
            ->with('oro_sso.enable_google_sso')
            ->willReturn(true);

        $this->cm
            ->expects($this->at(1))
            ->method('get')
            ->with('oro_sso.domains')
            ->willReturn(['google.com']);

        $userResponse = $this->createMock(UserResponseInterface::class);
        $userResponse
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('username');

        $userResponse
            ->expects($this->any())
            ->method('getResourceOwner')
            ->willReturn($this->createMock(ResourceOwnerInterface::class));

        $this->userManager
            ->expects($this->never())
            ->method('findUserBy');

        $this->oauthProvider->loadUserByOAuthUserResponse($userResponse);
    }
}
