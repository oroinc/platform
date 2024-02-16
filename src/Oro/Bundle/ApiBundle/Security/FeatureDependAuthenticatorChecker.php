<?php

namespace Oro\Bundle\ApiBundle\Security;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

/**
 * Checks if a feature depended authenticator is enabled.
 */
class FeatureDependAuthenticatorChecker
{
    public function __construct(
        private FeatureChecker $featureChecker,
        private array $featureDependedFirewalls
    ) {
    }

    public function isEnabled(AuthenticatorInterface $authenticator, string $firewallName): bool
    {
        if (!isset($this->featureDependedFirewalls[$firewallName])) {
            return true;
        }

        $firewall = $this->featureDependedFirewalls[$firewallName];
        if (!\in_array($authenticator::class, $firewall['feature_firewall_authenticators'] ?? [], true)) {
            return true;
        }

        return $this->featureChecker->isFeatureEnabled($firewall['feature_name']);
    }
}
