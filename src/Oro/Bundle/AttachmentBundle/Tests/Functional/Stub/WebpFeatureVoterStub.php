<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Stub;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * The decorator for WebpFeatureVoter that allows to substitute
 * the WebP processing strategy in functional tests.
 */
class WebpFeatureVoterStub implements VoterInterface
{
    private VoterInterface $webpFeatureVoter;
    private ?string $stubWebpStrategy = null;

    public function __construct(VoterInterface $webpFeatureVoter)
    {
        $this->webpFeatureVoter = $webpFeatureVoter;
    }

    public function setWebpStrategy(string $webpStrategy): void
    {
        $this->stubWebpStrategy = $webpStrategy;
    }

    public function resetWebpStrategy(): void
    {
        $this->stubWebpStrategy = null;
    }

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if (null !== $this->stubWebpStrategy && 'attachment_webp' === $feature) {
            return WebpConfiguration::DISABLED === $this->stubWebpStrategy
                ? self::FEATURE_DISABLED
                : self::FEATURE_ENABLED;
        }

        return $this->webpFeatureVoter->vote($feature, $scopeIdentifier);
    }
}
