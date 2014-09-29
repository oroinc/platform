<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Authentication\Provider\OrganizationRememberMeAuthenticationProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;

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
    }

    public function testSupports()
    {
        $user = new User(1);
        $organization = new Organization(2);
        $role = new Role('test');

        $user->setOrganizations(new ArrayCollection([$organization]));
        $user->setRoles(new ArrayCollection([$role]));

        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);
        $this->assertTrue($this->provider->supports($token));

        $token = new OrganizationRememberMeToken($user, 'another', 'testKey', $organization);
        $this->assertFalse($this->provider->supports($token));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\AbstractToken');
        $this->assertFalse($this->provider->supports($token));
    }

    public function testAuthenticate()
    {
        $user = new User(1);
        $organization = new Organization(2);
        $organization->setEnabled(true);
        $role = new Role('test');

        $user->setOrganizations(new ArrayCollection([$organization]));
        $user->setRoles(new ArrayCollection([$role]));

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

    public function testBadOrganizationAuthenticate()
    {
        $user = new User(1);
        $organization = new Organization(2);
        $organization->setEnabled(false);
        $role = new Role('test');

        $user->setOrganizations(new ArrayCollection([$organization]));
        $user->setRoles(new ArrayCollection([$role]));

        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);

        $this->userChecker->expects($this->once())
            ->method('checkPreAuth');

        $this->setExpectedException('Symfony\Component\Security\Core\Exception\BadCredentialsException');
        $this->provider->authenticate($token);
    }
}
