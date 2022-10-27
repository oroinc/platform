<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Provider;

use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Provider\OrganizationRememberMeAuthenticationProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactory;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class OrganizationRememberMeAuthenticationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userChecker;

    /** @var OrganizationRememberMeAuthenticationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->userChecker = $this->createMock(UserCheckerInterface::class);

        $this->provider = new OrganizationRememberMeAuthenticationProvider($this->userChecker, 'testKey', 'provider');
        $this->provider->setTokenFactory(new OrganizationRememberMeTokenFactory());
        $this->provider->setOrganizationGuesser(new OrganizationGuesser());
    }

    public function testSupports()
    {
        $organization = new Organization(2);
        $user = new User(1);
        $user->addOrganization($organization);

        $token = new OrganizationRememberMeToken($user, 'provider', 'testKey', $organization);
        $this->assertTrue($this->provider->supports($token));

        $token = new OrganizationRememberMeToken($user, 'another', 'testKey', $organization);
        $this->assertFalse($this->provider->supports($token));

        $token = $this->createMock(AbstractToken::class);
        $this->assertFalse($this->provider->supports($token));
    }

    public function testAuthenticateIfTokenFactoryIsNotSet()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Token Factory is not set in OrganizationRememberMeAuthenticationProvider.');

        $token = new OrganizationRememberMeToken(new User(1), 'provider', 'testKey', new Organization(2));
        $provider = new OrganizationRememberMeAuthenticationProvider($this->userChecker, 'testKey', 'provider');
        $provider->authenticate($token);
    }

    public function testAuthenticateIfOrganizationGuesserIsNotSet()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage(
            'Organization Guesser is not set in OrganizationRememberMeAuthenticationProvider.'
        );

        $token = new OrganizationRememberMeToken(new User(1), 'provider', 'testKey', new Organization(2));
        $provider = new OrganizationRememberMeAuthenticationProvider($this->userChecker, 'testKey', 'provider');
        $provider->setTokenFactory($this->createMock(OrganizationRememberMeTokenFactoryInterface::class));
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
            OrganizationRememberMeToken::class,
            $resultToken
        );
        $this->assertSame($user, $resultToken->getUser());
        $this->assertSame($organization, $resultToken->getOrganization());
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
            OrganizationRememberMeToken::class,
            $resultToken
        );
        $this->assertSame($user, $resultToken->getUser());
        $this->assertSame($organization, $resultToken->getOrganization());
    }

    public function testBadOrganizationAuthenticate()
    {
        $this->expectException(BadUserOrganizationException::class);
        $this->expectExceptionMessage('The user does not have access to organization "Inactive Org".');

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

    public function testNoAssignedOrganizations()
    {
        $this->expectException(BadUserOrganizationException::class);
        $this->expectExceptionMessage('The user does not have active organization assigned to it.');

        $user = new User(1);
        $token = new RememberMeToken($user, 'provider', 'testKey');

        $this->userChecker->expects($this->once())
            ->method('checkPreAuth');

        $this->provider->authenticate($token);
    }
}
