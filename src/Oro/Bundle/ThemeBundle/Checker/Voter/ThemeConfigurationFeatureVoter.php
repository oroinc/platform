<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Checker\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationTypeProvider;

/**
 * Feature voter for theme configuration management.
 *
 * Enables the 'theme_configuration' feature only when at least one theme configuration type
 * is available via ThemeConfigurationTypeProvider.
 * Disables the feature for applications without registered theme types (e.g., platform-only installations).
 */
class ThemeConfigurationFeatureVoter implements VoterInterface
{
    private const string FEATURE_NAME = 'theme_configuration';

    public function __construct(
        private readonly ThemeConfigurationTypeProvider $themeConfigurationTypeProvider
    ) {
    }

    #[\Override]
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature !== self::FEATURE_NAME) {
            return self::FEATURE_ABSTAIN;
        }

        return empty($this->themeConfigurationTypeProvider->getTypes())
            ? self::FEATURE_DISABLED
            : self::FEATURE_ENABLED;
    }
}
