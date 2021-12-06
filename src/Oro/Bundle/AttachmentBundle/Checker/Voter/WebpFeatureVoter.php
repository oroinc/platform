<?php

namespace Oro\Bundle\AttachmentBundle\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Decides if WebP feature is enabled depending on configuration
 */
class WebpFeatureVoter implements VoterInterface
{
    private string $webpStrategy;

    public function __construct(string $webpStrategy)
    {
        $this->webpStrategy = $webpStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature !== 'attachment_webp') {
            return self::FEATURE_ABSTAIN;
        }

        return $this->webpStrategy === WebpConfiguration::DISABLED
            ? self::FEATURE_DISABLED
            : self::FEATURE_ENABLED;
    }
}
