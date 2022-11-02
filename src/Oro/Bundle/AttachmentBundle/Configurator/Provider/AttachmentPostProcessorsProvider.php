<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessingVoter;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

/**
 * Is a provider that takes into account system settings to create a filter configuration.
 */
class AttachmentPostProcessorsProvider implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var array
     */
    private $postProcessorsConfigs = [];

    /**
     * @var null|bool
     */
    private $postProcessingEnabled = null;

    /**
     * @var null|bool
     */
    private $postProcessorsAllowed = null;

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function isPostProcessingEnabled(): bool
    {
        if (null === $this->postProcessingEnabled) {
            $this->postProcessingEnabled =
                $this->featureChecker->isFeatureEnabled(PostProcessingVoter::ATTACHMENT_POST_PROCESSING);
        }

        return $this->postProcessingEnabled;
    }

    public function isPostProcessorsAllowed(): bool
    {
        if (null === $this->postProcessorsAllowed) {
            $this->postProcessorsAllowed =
                $this->featureChecker->isFeatureEnabled('attachment_post_processors_allowed');
        }

        return $this->postProcessorsAllowed;
    }

    private function getSystemConfig(): array
    {
        if (!$this->postProcessorsConfigs) {
            $this->postProcessorsConfigs = [
                $this->configManager->get('oro_attachment.png_quality'),
                $this->configManager->get('oro_attachment.jpeg_quality'),
            ];
        }

        return $this->postProcessorsConfigs;
    }

    /**
     * @return array|array[]
     */
    public function getFilterConfig(): array
    {
        if ($this->isPostProcessorsAllowed()) {
            [$pngQuality, $jpegQuality] = $this->getSystemConfig();

            return [
                'pngquant' => ['quality' => $pngQuality],
                'jpegoptim' => ['strip_all' => true, 'max' => $jpegQuality, 'progressive' => false],
            ];
        }

        return [];
    }
}
