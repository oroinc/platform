<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Guesser\OrganizationGuesserInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\SecurityBundle\Exception\BadUserOrganizationException;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

class ImpersonationAuthenticatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UsernamePasswordOrganizationTokenFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenFactory;

    /** @var OrganizationGuesserInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $organizationGuesser;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ImpersonationAuthenticator */
    private $authenticator;

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

    public function testGetCredentials(): void
    {
        $this->assertEquals(
            'sample-token',
            $this->authenticator->getCredentials(
                new Request([ImpersonationAuthenticator::TOKEN_PARAMETER => 'sample-token'])
            )
        );
    }

    public function testCheckCredentials(): void
    {
        $this->assertTrue($this->authenticator->checkCredentials([], new User()));
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

    public function testSupportsRememberMe(): void
    {
        $this->assertFalse($this->authenticator->supportsRememberMe());
    }

    public function testCreateAuthenticatedTokenWhenOrganization(): void
    {
        $user = new User();
        $organization = $this->createMock(Organization::class);
        $roles = [new Role()];
        $user->setUserRoles($roles);

        $this->organizationGuesser->expects($this->once())
            ->method('guess')
            ->with($this->identicalTo($user), $this->isNull())
            ->willReturn($organization);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);
        $providerKey = 'sample-key';
        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo($user),
                $this->isNull(),
                $providerKey,
                $this->identicalTo($organization),
                $roles
            )
            ->willReturn($token);

        $this->assertSame(
            $token,
            $this->authenticator->createAuthenticatedToken($user, $providerKey)
        );
    }

    public function testCreateAuthenticatedTokenWhenNoOrganization(): void
    {
        $this->expectException(BadUserOrganizationException::class);
        $this->expectExceptionMessage("You don't have active organization assigned.");

        $this->authenticator->createAuthenticatedToken(new User(), 'sample-key');
    }
}
