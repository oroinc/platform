<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator;
use Oro\Bundle\SSOBundle\Event\CheckPassportEventSubscriber;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CredentialsInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CheckPassportEventSubscriberTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private CheckPassportEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->subscriber = new CheckPassportEventSubscriber(
            $this->configManager,
            'enable_sso_parameter',
            'sso_domains_parameter',
            'sso_only_login_parameter',
            'firewall_name'
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [CheckPassportEvent::class => 'onCheckPassport'],
            CheckPassportEventSubscriber::getSubscribedEvents()
        );
    }

    public function testOnCheckPassportWithNonAbstractLoginFormAuthenticator(): void
    {
        $authenticator = $this->createMock(AuthenticatorInterface::class);
        $passport = new Passport(
            $this->createMock(UserBadge::class),
            $this->createMock(CredentialsInterface::class),
            []
        );
        $event = new CheckPassportEvent($authenticator, $passport);

        $this->configManager->expects(self::never())
            ->method('get');

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithNonEmailHolderUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $authenticator = $this->createMock(AbstractLoginFormAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->configManager->expects(self::never())
            ->method('get');

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithNonExpectedFirewall(): void
    {
        $user = new User();
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $authenticator->expects(self::once())
            ->method('getFirewallName')
            ->willReturn('another_firewall');

        $this->configManager->expects(self::never())
            ->method('get');

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithDisabledSSO(): void
    {
        $user = new User();
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $authenticator->expects(self::once())
            ->method('getFirewallName')
            ->willReturn('firewall_name');

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('enable_sso_parameter')
            ->willReturn(false);

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithDisabledDomainsCheck(): void
    {
        $user = new User();
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $authenticator->expects(self::once())
            ->method('getFirewallName')
            ->willReturn('firewall_name');

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, false]
            ]);

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithEmptyDomains(): void
    {
        $user = new User();
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $authenticator->expects(self::once())
            ->method('getFirewallName')
            ->willReturn('firewall_name');

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, true],
                ['sso_domains_parameter', false, false, null, []]
            ]);

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithAllowedDomain(): void
    {
        $user = new User();
        $user->setEmail('test@allowed.com');
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $authenticator->expects(self::once())
            ->method('getFirewallName')
            ->willReturn('firewall_name');

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, true],
                ['sso_domains_parameter', false, false, null, ['notallowed1.com', 'notallowed2.com']]
            ]);

        $this->subscriber->onCheckPassport($event);
    }

    public function testOnCheckPassportWithNotAllowedDomain(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Authentication failed; '
            . 'Given user with email "test@notallowed2.com" should log in via SSO.');

        $user = new User();
        $user->setEmail('test@notallowed2.com');
        $authenticator = $this->createMock(UsernamePasswordOrganizationAuthenticator::class);
        $passport = $this->createMock(Passport::class);
        $event = new CheckPassportEvent($authenticator, $passport);

        $passport->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $authenticator->expects(self::once())
            ->method('getFirewallName')
            ->willReturn('firewall_name');

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, true],
                ['sso_domains_parameter', false, false, null, ['notallowed1.com', 'notallowed2.com']]
            ]);

        $this->subscriber->onCheckPassport($event);
    }
}
