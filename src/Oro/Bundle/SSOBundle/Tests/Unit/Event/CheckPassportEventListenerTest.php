<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SSOBundle\Event\CheckPassportEventListener;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;

class CheckPassportEventListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private CheckPassportEventListener $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new CheckPassportEventListener(
            $this->configManager,
            'enable_sso_parameter',
            'sso_domains_parameter',
            'sso_only_login_parameter',
            'firewall_name'
        );
    }

    public function testOnAuthenticationSuccessOnNonUsernamePasswordOrganizationToken(): void
    {
        $token = new ConsoleToken([]);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::never())
            ->method('get');

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithNonEmailHolderUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'firewall_name', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::never())
            ->method('get');

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithNonExpectedFirewall(): void
    {
        $user = new User();
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'another_firewall', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::never())
            ->method('get');

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithDisabledSSO(): void
    {
        $user = new User();
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'firewall_name', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('enable_sso_parameter')
            ->willReturn(false);

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithDisabledDomainsCheck(): void
    {
        $user = new User();
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'firewall_name', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, false]
            ]);

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithEmptyDomains(): void
    {
        $user = new User();
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'firewall_name', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, true],
                ['sso_domains_parameter', false, false, null, []]
            ]);

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithAllowedDomain(): void
    {
        $user = new User();
        $user->setEmail('test@another.com');
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'firewall_name', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, true],
                ['sso_domains_parameter', false, false, null, ['notallowed1.com', 'notallowed2.com']]
            ]);

        $this->listener->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessWithNotAllowedDomain(): void
    {
        $this->expectException(BadCredentialsException::class);
        $this->expectExceptionMessage('Authentication failed; '
            . 'Given user with email "test@notallowed2.com" should log in via SSO.');

        $user = new User();
        $user->setEmail('test@notallowed2.com');
        $token = new UsernamePasswordOrganizationToken($user, 'password', 'firewall_name', new Organization(), []);
        $event = new AuthenticationSuccessEvent($token);

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['enable_sso_parameter', false, false, null, true],
                ['sso_only_login_parameter', false, false, null, true],
                ['sso_domains_parameter', false, false, null, ['notallowed1.com', 'notallowed2.com']]
            ]);

        $this->listener->onAuthenticationSuccess($event);
    }
}
