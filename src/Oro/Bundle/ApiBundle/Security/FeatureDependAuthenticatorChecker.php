<?php

namespace Oro\Bundle\ApiBundle\Security;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;

/**
 * Check is enabled feature if disabled authenticator matched.
 */
class FeatureDependAuthenticatorChecker
{
    public function __construct(private FeatureChecker $featureChecker, private array $featureDependedFirewalls)
    {
    }

    public function isEnabled(AuthenticatorInterface $authenticator, string $firewallName): bool
    {
        if (isset($this->featureDependedFirewalls[$firewallName]['feature_firewall_authenticators'])
            && \in_array(
                $authenticator::class,
                $this->featureDependedFirewalls[$firewallName]['feature_firewall_authenticators']
            )
            && !$this->featureChecker->isFeatureEnabled($this->featureDependedFirewalls[$firewallName]['feature_name'])
        ) {
            return false;
        }

        return true;
    }
}
