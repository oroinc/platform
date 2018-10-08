<?php

namespace Oro\Bundle\SSOBundle\Tests\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOauthToken;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SSOBundle\Security\OAuthProvider;
use Oro\Bundle\SSOBundle\Security\OAuthToken;
use Oro\Bundle\SSOBundle\Security\OAuthTokenFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class OAuthProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OAuthProvider
     */
    private $oauthProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|OAuthAwareUserProviderInterface
     */
    private $userProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ResourceOwnerMap
     */
    private $resourceOwnerMap;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserCheckerInterface
     */
    private $userChecker;

    /**
     * @var OAuthTokenFactory
     */
    private $tokenFactory;

    public function setUp()
    {
        $this->userProvider = $this
                ->createMock('HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface');
        $this->resourceOwnerMap = $this->getMockBuilder('HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap')
                ->disableOriginalConstructor()
                ->getMock();
        $this->userChecker = $this->createMock('Symfony\Component\Security\Core\User\UserCheckerInterface');

        $this->tokenFactory = new OAuthTokenFactory();

        $this->oauthProvider = new OAuthProvider($this->userProvider, $this->resourceOwnerMap, $this->userChecker);
    }

    public function testSupportsShouldReturnTrueForOAuthToken()
    {
        $this->resourceOwnerMap->expects($this->once())
            ->method('hasResourceOwnerByName')
            ->with($this->equalTo('google'))
            ->will($this->returnValue(true));

        $token = new HWIOauthToken('token');
        $token->setResourceOwnerName('google');
        $this->assertTrue($this->oauthProvider->supports($token));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage Token Factory is not set in OAuthProvider.
     */
    public function testAuthenticateIfTokenFactoryIsNotSet()
    {
        $token = new OAuthToken('token');
        $this->oauthProvider->authenticate($token);
    }

    public function testTokenShouldBeAuthenticated()
    {
        $this->oauthProvider->setTokenFactory($this->tokenFactory);

        $token = new OAuthToken('token');
        $token->setResourceOwnerName('google');
        $organization = new Organization();
        $organization->setEnabled(true);
        $token->setOrganizationContext($organization);

        $userResponse = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');

        $resourceOwner = $this->createMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface');
        $resourceOwner
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('google'));

        $resourceOwner
            ->expects($this->any())
            ->method('getUserInformation')
            ->will($this->returnValue($userResponse));

        $this->resourceOwnerMap
            ->expects($this->any())
            ->method('getResourceOwnerByName')
            ->will($this->returnValue($resourceOwner));

        $user = new User();
        $user->addOrganization($organization);

        $this->userProvider
            ->expects($this->any())
            ->method('loadUserByOAuthUserResponse')
            ->with($userResponse)
            ->will($this->returnValue($user));

        $resultToken = $this->oauthProvider->authenticate($token);
        $this->assertInstanceOf('Oro\Bundle\SSOBundle\Security\OAuthToken', $resultToken);
        $this->assertSame($user, $resultToken->getUser());
        $this->assertEquals('google', $resultToken->getResourceOwnerName());
        $this->assertTrue($resultToken->isAuthenticated());
    }
}
