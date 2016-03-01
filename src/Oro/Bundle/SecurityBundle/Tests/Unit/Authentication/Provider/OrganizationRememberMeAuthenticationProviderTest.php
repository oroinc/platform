<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;

use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactory;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Provider\OrganizationRememberMeAuthenticationProvider;

class OrganizationRememberMeAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationRememberMeAuthenticationProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userChecker;

    public function setUp()
    {
        $this->userChecker = $this->getMock('Symfony\Component\Security\Core\User\UserCheckerInterface');
        $this->provider = new OrganizationRememberMeAuthenticationProvider($this->userChecker, 'testKey', 'provider');
        $this->provider->setTokenFactory(new OrganizationRememberMeTokenFactory());
    }

    public function testSupports()
    {
        $organization = new Organization(2);
        $user         = new User(1);
        $user->addOrganization($organization);

        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);
        $this->assertTrue($this->provider->supports($token));

        $token = new OrganizationRememberMeToken($user, 'another', 'testKey', $organization);
        $this->assertFalse($this->provider->supports($token));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\AbstractToken');
        $this->assertFalse($this->provider->supports($token));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage Token Factory is not set in OrganizationRememberMeAuthenticationProvider.
     */
    public function testAuthenticateIfTokenFactoryIsNotSet()
    {
        $user = new User(1);
        $organization = new Organization(2);
        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);
        $provider = new OrganizationRememberMeAuthenticationProvider($this->userChecker, 'testKey', 'provider');
        $provider->authenticate($token);
    }

    public function testAuthenticate()
    {
        $organization = new Organization(2);
        $organization->setEnabled(true);
        $user = new User(1);
        $user->addOrganization($organization);

        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);

        $this->userChecker->expects($this->once())
            ->method('checkPreAuth');

        $resultToken = $this->provider->authenticate($token);
        $this->assertInstanceOf(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken',
            $resultToken
        );
        $this->assertSame($user, $resultToken->getUser());
        $this->assertSame($organization, $resultToken->getOrganizationContext());
    }

    public function testOrganizationGuessedFromUser()
    {
        $organization = new Organization(2);
        $organization->setEnabled(true);
        $user = new User(1);
        $user->addOrganization($organization);

        $token = new RememberMeToken($user, 'provider', 'testKey');
        $this->userChecker->expects($this->once())
            ->method('checkPreAuth');

        $resultToken = $this->provider->authenticate($token);
        $this->assertInstanceOf(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken',
            $resultToken
        );
        $this->assertSame($user, $resultToken->getUser());
        $this->assertSame($organization, $resultToken->getOrganizationContext());
    }

    /**
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage You don't have access to organization 'Inactive Org'
     */
    public function testBadOrganizationAuthenticate()
    {
        $organization = new Organization(2);
        $organization->setEnabled(false);
        $organization->setName('Inactive Org');
        $user = new User(1);
        $user->addOrganization($organization);

        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);

        $this->userChecker->expects($this->once())
            ->method('checkPreAuth');

        $this->provider->authenticate($token);
    }

    /**
     * @expectedException        \Symfony\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage You don't have active organization assigned.
     */
    public function testNoAssignedOrganizations()
    {
        $user  = new User(1);
        $token = new RememberMeToken($user, 'provider', 'testKey');

        $this->userChecker->expects($this->once())
            ->method('checkPreAuth');

        $this->provider->authenticate($token);
    }
}
