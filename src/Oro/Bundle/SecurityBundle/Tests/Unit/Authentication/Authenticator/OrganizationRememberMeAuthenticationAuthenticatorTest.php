<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Authenticator;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Authenticator\OrganizationRememberMeAuthenticationAuthenticator;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\RememberMe\RememberMeDetails;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

class OrganizationRememberMeAuthenticationAuthenticatorTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&RememberMeHandlerInterface $rememberMeHandler;
    private TokenStorage $tokenStorage;
    private OrganizationRememberMeAuthenticationAuthenticator $authenticator;
    private MockObject&OrganizationGuesserInterface $organizationGuesser;
    private MockObject&OrganizationRememberMeTokenFactoryInterface $tokenFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->rememberMeHandler = $this->createMock(RememberMeHandlerInterface::class);
        $this->tokenStorage = new TokenStorage();
        $this->organizationGuesser = $this->createMock(OrganizationGuesserInterface::class);
        $this->tokenFactory = $this->createMock(OrganizationRememberMeTokenFactoryInterface::class);
        $this->authenticator = new OrganizationRememberMeAuthenticationAuthenticator(
            $this->rememberMeHandler,
            's3cr3t',
            $this->tokenStorage,
            '_remember_me_cookie'
        );
        $this->authenticator->setTokenFactory($this->tokenFactory);
        $this->authenticator->setOrganizationGuesser($this->organizationGuesser);
    }

    public function testAuthenticate()
    {
        $user = $this->createMock(AbstractUser::class);
        $organization = new Organization();

        $this->organizationGuesser->expects($this->once())
            ->method('guess')
            ->with($this->identicalTo($user))
            ->willReturn($organization);

        $this->rememberMeHandler->expects($this->once())
            ->method('consumeRememberMeCookie')
            ->willReturn($user);

        $rememberMeDetails = new RememberMeDetails(User::class, 'wouter', 1, 'secret');
        $request = Request::create('/', 'GET', [], ['_remember_me_cookie' => $rememberMeDetails->toString()]);

        $resultPassport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $resultPassport);
        $this->assertEquals($organization, $resultPassport->getAttribute('organization'));
    }

    public function testCreateToken(): void
    {
        $user = $this->createMock(AbstractUser::class);
        $organization = $this->createMock(Organization::class);
        $token = $this->createMock(OrganizationRememberMeToken::class);
        $firewallName = 'main';

        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo($user),
                $this->equalTo($firewallName),
                $this->equalTo('s3cr3t'),
                $this->identicalTo($organization)
            )
            ->willReturn($token);

        $passport = $this->createMock(Passport::class);
        $passport->expects($this->once())
            ->method('getAttribute')
            ->with(
                $this->identicalTo('organization'),
            )
            ->willReturn($organization);

        $passport->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $resultToken = $this->authenticator->createToken($passport, $firewallName);

        $this->assertInstanceOf(TokenInterface::class, $resultToken);
        $this->assertSame($token, $resultToken);
    }
}
