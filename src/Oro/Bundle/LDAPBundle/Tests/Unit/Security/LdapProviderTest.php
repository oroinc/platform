<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Security;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\LDAPBundle\Security\LdapProvider;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LdapProviderTest extends \PHPUnit_Framework_TestCase
{
    private $providerKey = 'fr3d_ldap';
    private $userProvider;
    private $ldapManager;
    private $cm;

    private $ldapProvider;

    public function setUp()
    {
        $userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');

        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->ldapManager = $this->getMock('FR3D\LdapBundle\Ldap\LdapManagerInterface');
        $this->cm = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ldapProvider = new LdapProvider(
            $userChecker,
            $this->providerKey,
            $this->userProvider,
            $this->ldapManager,
            $this->cm
        );
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testAuthenticateShouldThrowExceptionIfLdapLoginIsDisabled()
    {
        $this->cm->expects($this->once())
            ->method('get')
            ->with('oro_ldap.server_enable_login')
            ->will($this->returnValue(false));

        $this->ldapProvider->authenticate(new UsernamePasswordToken('user', 'credentials', $this->providerKey));
    }
    
    public function testTokenShouldBeAuthenticated()
    {
        $token = new UsernamePasswordToken('user', 'credentials', $this->providerKey);

        $organization = new Organization();
        $organization->setEnabled(true);

        $user = new TestingUser();
        $user->addOrganization($organization);

        $this->cm->expects($this->once())
            ->method('get')
            ->with('oro_ldap.server_enable_login')
            ->will($this->returnValue(true));

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('user')
            ->will($this->returnValue($user));

        $this->ldapManager->expects($this->once())
            ->method('bind')
            ->will($this->returnValue(true));

        $resultToken = $this->ldapProvider->authenticate($token);

        $this->assertInstanceOf(
            'Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken',
            $resultToken
        );
        $this->assertSame($user, $resultToken->getUser());
        $this->assertEquals('credentials', $resultToken->getCredentials());
        $this->assertEquals($this->providerKey, $resultToken->getProviderKey());
        $this->assertEquals($organization, $resultToken->getOrganizationContext());
    }
}
