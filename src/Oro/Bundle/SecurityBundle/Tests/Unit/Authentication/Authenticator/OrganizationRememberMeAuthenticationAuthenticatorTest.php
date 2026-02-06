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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\RememberMe\RememberMeDetails;
use Symfony\Component\Security\Http\RememberMe\RememberMeHandlerInterface;

class OrganizationRememberMeAuthenticationAuthenticatorTest extends TestCase
{
    private RememberMeHandlerInterface&MockObject $rememberMeHandler;
    private OrganizationGuesserInterface&MockObject $organizationGuesser;
    private OrganizationRememberMeTokenFactoryInterface&MockObject $tokenFactory;
    private OrganizationRememberMeAuthenticationAuthenticator $authenticator;

    #[\Override]
    protected function setUp(): void
    {
        $this->rememberMeHandler = $this->createMock(RememberMeHandlerInterface::class);
        $this->organizationGuesser = $this->createMock(OrganizationGuesserInterface::class);
        $this->tokenFactory = $this->createMock(OrganizationRememberMeTokenFactoryInterface::class);

        $this->authenticator = new OrganizationRememberMeAuthenticationAuthenticator(
            $this->rememberMeHandler,
            's3cr3t',
            new TokenStorage(),
            '_remember_me_cookie'
        );
        $this->authenticator->setTokenFactory($this->tokenFactory);
        $this->authenticator->setOrganizationGuesser($this->organizationGuesser);
    }

    public function testAuthenticate(): void
    {
        $user = $this->createMock(AbstractUser::class);
        $organization = new Organization();

        $this->organizationGuesser->expects(self::once())
            ->method('guess')
            ->with(self::identicalTo($user))
            ->willReturn($organization);

        $this->rememberMeHandler->expects(self::once())
            ->method('consumeRememberMeCookie')
            ->willReturn($user);

        $rememberMeDetails = new RememberMeDetails(User::class, 'wouter', 1, 'secret');
        $request = Request::create('/', 'GET', [], ['_remember_me_cookie' => $rememberMeDetails->toString()]);

        $resultPassport = $this->authenticator->authenticate($request);

        self::assertEquals($organization, $resultPassport->getAttribute('organization'));
    }

    public function testCreateToken(): void
    {
        $user = $this->createMock(AbstractUser::class);
        $organization = $this->createMock(Organization::class);
        $token = $this->createMock(OrganizationRememberMeToken::class);
        $firewallName = 'main';

        $this->tokenFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($user), $firewallName, 's3cr3t', self::identicalTo($organization))
            ->willReturn($token);

        $passport = $this->createMock(Passport::class);
        $passport->expects(self::once())
            ->method('getAttribute')
            ->with('organization')
            ->willReturn($organization);

        $passport->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        self::assertSame($token, $this->authenticator->createToken($passport, $firewallName));
    }
}
