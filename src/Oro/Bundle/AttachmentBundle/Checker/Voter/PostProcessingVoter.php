<?php

namespace Oro\Bundle\AttachmentBundle\Checker\Voter;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Indicates whether to use post processors.
 */
class PostProcessingVoter implements VoterInterface
{
    public const ATTACHMENT_POST_PROCESSING = 'attachment_post_processing';

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @inhericDoc
     */
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature === self::ATTACHMENT_POST_PROCESSING) {
            return $this->isDefaultQualityUsed() ? self::FEATURE_DISABLED : self::FEATURE_ENABLED;
        }

        return self::FEATURE_ABSTAIN;
    }

    private function isDefaultQualityUsed(): bool
    {
        $pngQuality = $this->configManager->get('oro_attachment.png_quality');
        $jpegQuality = $this->configManager->get('oro_attachment.jpeg_quality');

        return Configuration::PNG_QUALITY === $pngQuality && Configuration::JPEG_QUALITY === $jpegQuality;
    }
}
