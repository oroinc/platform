<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Security;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;

class ImpersonationAuthenticatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var UsernamePasswordOrganizationTokenFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenFactory;

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
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->authenticator = new ImpersonationAuthenticator(
            $this->doctrine,
            $this->tokenFactory,
            $this->eventDispatcher,
            $this->router
        );
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->authenticator
            ->supports(new Request([ImpersonationAuthenticator::TOKEN_PARAMETER => 'sample-token'])));

        $this->assertFalse($this->authenticator
            ->supports(new Request()));
    }

    public function testGetCredentials(): void
    {
        $this->assertEquals('sample-token', $this->authenticator
            ->getCredentials(new Request([ImpersonationAuthenticator::TOKEN_PARAMETER => 'sample-token'])));
    }

    public function testCheckCredentials(): void
    {
        $this->assertTrue($this->authenticator->checkCredentials([], new User()));
    }

    public function testOnAuthenticationFailure(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_user_security_login')
            ->willReturn($url = '/sample/url');

        $request = new Request();
        $request->setSession($session = $this->createMock(Session::class));

        $session
            ->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $exception = new AuthenticationException());

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($url, $response->getTargetUrl());
    }

    public function testStartWhenAuthException(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_user_security_login')
            ->willReturn($url = '/sample/url');

        $request = new Request();
        $request->setSession($session = $this->createMock(Session::class));

        $session
            ->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $exception = new AuthenticationException());

        $response = $this->authenticator->start($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($url, $response->getTargetUrl());
    }

    public function testStartWhenNoAuthException(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_user_security_login')
            ->willReturn($url = '/sample/url');

        $request = new Request();
        $request->setSession($session = $this->createMock(Session::class));

        $session
            ->expects($this->never())
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
        $user->setOrganization($organization = $this->createMock(Organization::class));
        $user->setOrganizations(new ArrayCollection([$organization]));
        $user->setRoles($roles = [new Role()]);

        $organization
            ->method('isEnabled')
            ->willReturn(true);

        $this->tokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($user, null, $providerKey = 'sample-key', $organization, $roles)
            ->willReturn($token = $this->createMock(UsernamePasswordOrganizationToken::class));

        $this->assertSame(
            $token,
            $this->authenticator->createAuthenticatedToken($user, $providerKey)
        );
    }

    public function testCreateAuthenticatedTokenWhenNoOrganization(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('You don\'t have active organization assigned.');

        $this->authenticator->createAuthenticatedToken(new User(), 'sample-key');
    }
}
