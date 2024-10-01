<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;
use Oro\Bundle\UserBundle\Exception\ImpersonationAuthenticationException;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadImpersonationData;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ImpersonationAuthenticatorTest extends WebTestCase
{
    /** @var ImpersonationAuthenticator */
    private $authenticator;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadImpersonationData::class]);

        $this->authenticator = new ImpersonationAuthenticator(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->get('oro_security.token.factory.username_password_organization'),
            $this->getContainer()->get('oro_security.authentication.organization_guesser'),
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('router')
        );
    }

    public function testCreateTokenSuccess()
    {
        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);
        $passport = new SelfValidatingPassport(
            new UserBadge($impersonation->getToken(), [$this->authenticator, 'getUserByImpersonationToken'])
        );
        $token = $this->authenticator->createToken($passport, 'test');

        self::assertInstanceOf(TokenInterface::class, $token);
    }

    public function testSupportSuccess()
    {
        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);
        $request = new Request();
        $request->query = new InputBag(['_impersonation_token' => $impersonation->getToken()]);

        self::assertTrue($this->authenticator->supports($request));
    }

    public function testSupportFailure()
    {
        $request = new Request();
        $request->query = new InputBag([]);

        self::assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateSuccess(): void
    {
        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);
        $request = new Request();
        $request->query = new InputBag(['_impersonation_token' => $impersonation->getToken()]);
        $passport = $this->authenticator->authenticate($request);

        $this->assertSame($impersonation->getUser(), $passport->getUser());
    }

    public function testAuthenticateWhenTokenNotFound(): void
    {
        $request = new Request();
        $this->expectException(ImpersonationAuthenticationException::class);
        $this->expectExceptionMessage('Impersonation token is not set.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateUserWhenTokenExpired(): void
    {
        $this->expectException(ImpersonationAuthenticationException::class);
        $this->expectExceptionMessage('Impersonation token has expired.');

        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER_EXPIRED);
        $request = new Request();
        $request->query = new InputBag(['_impersonation_token' => $impersonation->getToken()]);
        $passport = $this->authenticator->authenticate($request);

        self::assertSame($impersonation->getUser(), $passport->getUser());
    }

    public function testOnAuthenticationSuccess(): void
    {
        $authenticator = new ImpersonationAuthenticator(
            $this->getContainer()->get('doctrine'),
            $this->getContainer()->get('oro_security.token.factory.username_password_organization'),
            $this->getContainer()->get('oro_security.authentication.organization_guesser'),
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class),
            $this->getContainer()->get('router')
        );

        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new ImpersonationSuccessEvent($impersonation), ImpersonationSuccessEvent::EVENT_NAME);

        $request = new Request([ImpersonationAuthenticator::TOKEN_PARAMETER => $impersonation->getToken()]);
        $request->server->set('REMOTE_ADDR', $ip = '1.2.3.4');

        $impersonationUser = $impersonation->getUser();
        $authenticator->onAuthenticationSuccess(
            $request,
            $token = new ImpersonationToken($impersonationUser, $impersonationUser->getOrganization()),
            'sample-key'
        );

        $this->assertEquals($ip, $impersonation->getIpAddress());
        $this->assertNotEmpty($impersonation->getLoginAt());
        $this->assertEquals($impersonation->getId(), $token->getAttribute('IMPERSONATION'));
    }

    /**
     * @depends testOnAuthenticationSuccess
     */
    public function testGetUserWhenTokenAlreadyUsed(): void
    {
        $this->expectException(ImpersonationAuthenticationException::class);
        $this->expectExceptionMessage('Impersonation token has already been used.');

        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);
        $request = new Request();
        $request->query = new InputBag(['_impersonation_token' => $impersonation->getToken()]);
        $passport = $this->authenticator->authenticate($request);

        self::assertSame($impersonation->getUser(), $passport->getUser());
    }

    public function testEntryPointWithoutException()
    {
        $request = new Request();
        $authException = null;
        $result = $this->authenticator->start($request, $authException);

        self::assertTrue($result instanceof RedirectResponse);
    }
}
