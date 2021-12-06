<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Oro\Bundle\UserBundle\Event\ImpersonationSuccessEvent;
use Oro\Bundle\UserBundle\Security\ImpersonationAuthenticator;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadImpersonationData;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class ImpersonationAuthenticatorTest extends WebTestCase
{
    /** @var ImpersonationAuthenticator */
    private $authenticator;

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

    public function testGetUser(): void
    {
        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);
        $user = $this->authenticator
            ->getUser($impersonation->getToken(), $this->getContainer()->get('oro_user.tests.security.provider'));

        $this->assertSame($impersonation->getUser(), $user);
    }

    public function testGetUserWhenTokenNotFound(): void
    {
        $this->expectException(AuthenticationCredentialsNotFoundException::class);

        $this->authenticator
            ->getUser('invalid-token', $this->getContainer()->get('oro_user.tests.security.provider'));
    }

    public function testGetUserWhenTokenExpired(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Impersonation token has expired.');

        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER_EXPIRED);

        $this->authenticator
            ->getUser($impersonation->getToken(), $this->getContainer()->get('oro_user.tests.security.provider'));
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
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Impersonation token has already been used.');

        /** @var Impersonation $impersonation */
        $impersonation = $this->getReference(LoadImpersonationData::IMPERSONATION_SIMPLE_USER);

        $this->authenticator
            ->getUser($impersonation->getToken(), $this->getContainer()->get('oro_user.tests.security.provider'));
    }
}
