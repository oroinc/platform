<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Security;

use Oro\Bundle\ApiBundle\Security\FeatureDependAuthenticatorChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Authentication\Authenticator\UsernamePasswordOrganizationAuthenticator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\HttpUtils;

class FeatureDependAuthenticatorCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FeatureDependAuthenticatorChecker */
    private $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->checker = new FeatureDependAuthenticatorChecker(
            $this->featureChecker,
            [
                'firewall1' => [
                    'feature_name'                    => 'feature1',
                    'feature_firewall_authenticators' => [UsernamePasswordOrganizationAuthenticator::class]
                ],
                'firewall2' => [
                    'feature_name'                    => 'feature2',
                    'feature_firewall_authenticators' => []
                ],
                'firewall3' => [
                    'feature_name' => 'feature3'
                ]
            ]
        );
    }

    private function getAuthenticator(): AuthenticatorInterface
    {
        return new UsernamePasswordOrganizationAuthenticator(
            $this->createMock(HttpUtils::class),
            $this->createMock(UserProviderInterface::class),
            $this->createMock(AuthenticationSuccessHandlerInterface::class),
            $this->createMock(AuthenticationFailureHandlerInterface::class),
            [],
            'test',
            []
        );
    }

    public function testIsEnabledWhenAuthenticatorEnabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(true);

        self::assertTrue($this->checker->isEnabled($this->getAuthenticator(), 'firewall1'));
    }

    public function testIsEnabledWhenAuthenticatorDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1')
            ->willReturn(false);

        self::assertFalse($this->checker->isEnabled($this->getAuthenticator(), 'firewall1'));
    }

    public function testIsEnabledForNotFeatureDependedAuthenticator(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertTrue($this->checker->isEnabled($this->createMock(AuthenticatorInterface::class), 'firewall1'));
    }

    public function testIsEnabledWhenFeatureDependedFirewallHasEmptyAuthenticators(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertTrue($this->checker->isEnabled($this->getAuthenticator(), 'firewall2'));
    }

    public function testIsEnabledWhenFeatureDependedFirewallDoesNotHaveAuthenticators(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertTrue($this->checker->isEnabled($this->getAuthenticator(), 'firewall3'));
    }

    public function testIsEnabledForNotSupportedFirewall(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertTrue($this->checker->isEnabled($this->getAuthenticator(), 'firewall4'));
    }
}
