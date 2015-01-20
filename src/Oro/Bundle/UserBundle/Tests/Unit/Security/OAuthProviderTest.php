<?php

namespace Oro\Bundle\UserBundle\Tests\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\OAuthProvider;
use Oro\Bundle\UserBundle\Security\OAuthToken;

class OAuthProviderTest extends \PHPUnit_Framework_TestCase
{
    private $oauthProvider;
    private $userProvider;
    private $resourceOwnerMap;
    private $userChecker;

    public function setUp()
    {
        $this->userProvider = $this->getMock('HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface');
        $this->resourceOwnerMap = $this->getMockBuilder('HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap')
                ->disableOriginalConstructor()
                ->getMock();
        $this->userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        
        $this->oauthProvider = new OAuthProvider($this->userProvider, $this->resourceOwnerMap, $this->userChecker);
    }

    public function testSupportsShuldReturnTrueForOAuthToken()
    {
        $token = new OAuthToken('token');
        $this->assertTrue($this->oauthProvider->supports($token));
    }
    
    public function testTokenShouldBeAuthenticated()
    {
        $token = new OAuthToken('token');
        $token->setResourceOwnerName('google');

        $userResponse = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface');
        
        $resourceOwner = $this->getMock('HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface');
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
        $user->setOrganization(new Organization());

        $this->userProvider
            ->expects($this->any())
            ->method('loadUserByOAuthUserResponse')
            ->with($userResponse)
            ->will($this->returnValue($user));

        $resultToken = $this->oauthProvider->authenticate($token);
        $this->assertInstanceOf('Oro\Bundle\UserBundle\Security\OAuthToken', $resultToken);
        $this->assertSame($user, $resultToken->getUser());
        $this->assertEquals('google', $resultToken->getResourceOwnerName());
        $this->assertTrue($resultToken->isAuthenticated());
    }
}
