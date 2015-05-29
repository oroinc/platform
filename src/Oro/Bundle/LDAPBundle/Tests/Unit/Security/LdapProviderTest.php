<?php

namespace Oro\Bundle\LDAPBundle\Tests\Unit\Security;

use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Oro\Bundle\LDAPBundle\Security\LdapProvider;
use Oro\Bundle\LDAPBundle\Tests\Unit\Stub\TestingUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LdapProviderTest extends \PHPUnit_Framework_TestCase
{
    private $providerKey = 'fr3d_ldap';
    private $userProvider;
    /** @var ChannelManagerProvider */
    private $managerProvider;

    private $ldapProvider;

    public function setUp()
    {
        $userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');

        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->managerProvider = $this->getMockBuilder('Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ldapProvider = new LdapProvider(
            $userChecker,
            $this->providerKey,
            $this->userProvider,
            $this->managerProvider
        );
    }
    
    public function testTokenShouldBeAuthenticated()
    {
        $token = new UsernamePasswordToken('user', 'credentials', $this->providerKey);

        $organization = new Organization();
        $organization->setEnabled(true);

        $user = new TestingUser();
        $user->addOrganization($organization);

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('user')
            ->will($this->returnValue($user));

        $this->managerProvider->expects($this->once())
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
