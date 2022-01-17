<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Security;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOauthToken;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SSOBundle\Security\OAuthProvider;
use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\SSOBundle\Security\OAuthTokenFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class OAuthProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var OAuthProvider */
    private $oauthProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OAuthAwareUserProviderInterface */
    private $userProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourceOwnerMap */
    private $resourceOwnerMap;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UserCheckerInterface */
    private $userChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface */
    private $tokenStorage;

    /** @var OAuthTokenFactory */
    private $tokenFactory;

    /** @var OrganizationGuesserInterface */
    private $organizationGuesser;

    protected function setUp(): void
    {
        $this->userProvider = $this->createMock(OAuthAwareUserProviderInterface::class);
        $this->resourceOwnerMap = $this->createMock(ResourceOwnerMap::class);
        $this->userChecker = $this->createMock(UserCheckerInterface::class);
        $this->tokenFactory = new OAuthTokenFactory();
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->organizationGuesser = new OrganizationGuesser();

        $this->oauthProvider = new OAuthProvider(
            $this->userProvider,
            $this->resourceOwnerMap,
            $this->userChecker,
            $this->tokenStorage
        );
    }

    public function testSupportsShouldReturnTrueForKnownOAuthToken()
    {
        $resourceOwnerName = 'test_resource_owner';
        $this->resourceOwnerMap->expects($this->once())
            ->method('hasResourceOwnerByName')
            ->with($resourceOwnerName)
            ->willReturn(true);

        $token = new HWIOauthToken('token');
        $token->setResourceOwnerName($resourceOwnerName);
        $this->assertTrue($this->oauthProvider->supports($token));
    }

    public function testSupportsShouldReturnTrueForUnknownOAuthToken()
    {
        $resourceOwnerName = 'test_resource_owner';
        $this->resourceOwnerMap->expects($this->once())
            ->method('hasResourceOwnerByName')
            ->with($resourceOwnerName)
            ->willReturn(false);

        $token = new HWIOauthToken('token');
        $token->setResourceOwnerName($resourceOwnerName);
        $this->assertFalse($this->oauthProvider->supports($token));
    }

    public function testAuthenticateIfTokenFactoryIsNotSet()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token Factory is not set in OAuthProvider.');

        $token = new OAuthToken('token');
        $this->oauthProvider->authenticate($token);
    }

    public function testAuthenticateIfOrganizationGuesserIsNotSet()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Organization Guesser is not set in OAuthProvider.');

        $this->oauthProvider->setTokenFactory($this->tokenFactory);

        $token = new OAuthToken('token');
        $this->oauthProvider->authenticate($token);
    }

    public function testTokenShouldBeAuthenticated()
    {
        $this->oauthProvider->setTokenFactory($this->tokenFactory);
        $this->oauthProvider->setOrganizationGuesser($this->organizationGuesser);

        $resourceOwnerName = 'test_resource_owner';
        $token = new OAuthToken('token');
        $token->setResourceOwnerName($resourceOwnerName);
        $organization = new Organization();
        $organization->setEnabled(true);
        $token->setOrganization($organization);

        $userResponse = $this->createMock(UserResponseInterface::class);

        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);
        $resourceOwner->expects($this->once())
            ->method('getName')
            ->willReturn($resourceOwnerName);

        $resourceOwner->expects($this->once())
            ->method('getUserInformation')
            ->willReturn($userResponse);

        $this->resourceOwnerMap->expects($this->once())
            ->method('getResourceOwnerByName')
            ->willReturn($resourceOwner);

        $user = new User();
        $user->addOrganization($organization);

        $this->userProvider->expects($this->once())
            ->method('loadUserByOAuthUserResponse')
            ->with($userResponse)
            ->willReturn($user);

        $resultToken = $this->oauthProvider->authenticate($token);
        $this->assertInstanceOf(OAuthToken::class, $resultToken);
        $this->assertSame($user, $resultToken->getUser());
        $this->assertEquals($resourceOwnerName, $resultToken->getResourceOwnerName());
        $this->assertTrue($resultToken->isAuthenticated());
    }
}
