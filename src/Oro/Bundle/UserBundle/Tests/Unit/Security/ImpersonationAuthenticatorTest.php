<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesser;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ImpersonationAuthenticatorTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private UsernamePasswordOrganizationTokenFactoryInterface&MockObject $tokenFactory;
    private OrganizationGuesserInterface&MockObject $organizationGuesser;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private UrlGeneratorInterface&MockObject $router;
    private ImpersonationAuthenticator $authenticator;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenFactory = $this->createMock(UsernamePasswordOrganizationTokenFactoryInterface::class);
        $this->organizationGuesser = $this->createMock(OrganizationGuesserInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->authenticator = new ImpersonationAuthenticator(
            $this->doctrine,
            $this->tokenFactory,
            $this->organizationGuesser,
            $this->eventDispatcher,
            $this->router
        );
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->authenticator->supports(
            new Request([ImpersonationAuthenticator::TOKEN_PARAMETER => 'sample-token'])
        ));

        $this->assertFalse($this->authenticator->supports(new Request()));
    }

    public function testOnAuthenticationFailure(): void
    {
        $url = '/sample/url';
        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_user_security_login')
            ->willReturn($url);

        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);

        $exception = new AuthenticationException();
        $session->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $exception);

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($url, $response->getTargetUrl());
    }

    public function testStartWhenAuthException(): void
    {
        $url = '/sample/url';
        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_user_security_login')
            ->willReturn($url);

        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);

        $exception = new AuthenticationException();
        $session->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $exception);

        $response = $this->authenticator->start($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($url, $response->getTargetUrl());
    }

    public function testStartWhenNoAuthException(): void
    {
        $url = '/sample/url';
        $this->router->expects($this->once())
            ->method('generate')
            ->with('oro_user_security_login')
            ->willReturn($url);

        $request = new Request();
        $session = $this->createMock(Session::class);
        $request->setSession($session);

        $session->expects($this->never())
            ->method('set');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($url, $response->getTargetUrl());
    }

    public function testCreateTokenWhenOrganization(): void
    {
        $user = new User();
        $user->setUsername('test_user');
        $organization = $this->createMock(Organization::class);
        $roles = [new Role()];
        $user->setUserRoles($roles);
        $passport = new SelfValidatingPassport(
            new UserBadge('test_user', fn () => $user)
        );

        $this->organizationGuesser->expects($this->once())
            ->method('guess')
            ->with($this->identicalTo($user))
            ->willReturn($organization);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $firewallName = 'sample-key';
        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo($user),
                $firewallName,
                $this->identicalTo($organization),
                $roles
            )
            ->willReturn($token);

        $this->assertSame(
            $token,
            $this->authenticator->createToken($passport, $firewallName)
        );
    }

    public function testCreateTokenWhenNoOrganization(): void
    {
        $authenticator = new ImpersonationAuthenticator(
            $this->doctrine,
            $this->tokenFactory,
            new OrganizationGuesser(),
            $this->eventDispatcher,
            $this->router
        );
        $user = new User();
        $user->setUsername('test_user');
        $this->expectException(BadUserOrganizationException::class);
        $this->expectExceptionMessage('The user does not have an active organization assigned to it.');
        $passport = new SelfValidatingPassport(
            new UserBadge('test_user', fn () => $user)
        );

        $authenticator->createToken($passport, 'sample-key');
    }
}
