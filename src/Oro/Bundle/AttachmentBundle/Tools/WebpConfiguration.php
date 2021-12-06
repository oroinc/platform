<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides methods to work with WebP configurations.
 */
class WebpConfiguration
{
    /**
     * Always convert images to WebP, ignore the old browsers that do not support the WebP format.
     */
    public const ENABLED_FOR_ALL = 'for_all';

    /**
     * Convert images to WebP for supported browsers.
     * Return images in their original format for the old browsers that do not support the WebP format.
     */
    public const ENABLED_IF_SUPPORTED = 'if_supported';

    /**
     * Do not convert images to WebP.
     */
    public const DISABLED = 'disabled';

    private ConfigManager $systemConfigManager;

    private string $webpStrategy;

    public function __construct(ConfigManager $systemConfigManager, string $webpStrategy)
    {
        $this->systemConfigManager = $systemConfigManager;
        $this->webpStrategy = $webpStrategy;
    }

    public function getWebpQuality(): int
    {
        $quality = (int)$this->systemConfigManager->get('oro_attachment.webp_quality');

        return $quality > 0 && $quality <= 100 ? $quality : Configuration::WEBP_QUALITY;
    }

    public function isEnabledIfSupported(): bool
    {
        return $this->webpStrategy === self::ENABLED_IF_SUPPORTED;
    }

    public function isEnabledForAll(): bool
    {
        return $this->webpStrategy === self::ENABLED_FOR_ALL;
    }

    public function isDisabled(): bool
    {
        return $this->webpStrategy === self::DISABLED;
    }
}
