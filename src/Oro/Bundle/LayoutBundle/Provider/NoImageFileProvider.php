<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;

class NoImageFileProvider
{
    protected const DEFAULT_IMAGE = '/bundles/orolayout/images/no_image.png';

    protected const DEFAULT_FILTER = 'product_no_image_default';

    /** @var LayoutContextHolder */
    private $contextHolder;

    /** @var ThemeManager */
    private $themeManager;

    /** @var ConfigManager */
    private $configManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CacheManager  */
    private $imagineCacheManager;

    /** @var AttachmentManager  */
    private $attachmentManager;

    /**
     * @param LayoutContextHolder $contextHolder
     * @param ThemeManager $themeManager
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     * @param CacheManager $imagineCacheManager
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        LayoutContextHolder $contextHolder,
        ThemeManager $themeManager,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        CacheManager $imagineCacheManager,
        AttachmentManager $attachmentManager
    ) {
        $this->contextHolder = $contextHolder;
        $this->themeManager = $themeManager;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param string|null $filter
     * @return string
     */
    public function getNoImagePath(?string $filter = null): string
    {
        $filter = $filter ?: self::DEFAULT_FILTER;
        $configKey = Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_PLACEHOLDER;
        $imageId = $this->configManager->get($configKey);
        if ($imageId && $image = $this->doctrineHelper->getEntity(File::class, $imageId)) {
            return $this->attachmentManager->getFilteredImageUrl($image, $filter);
        }

        $themeName = $this->getThemeName();
        if ($themeName && $themeNoImagePath = $this->themeManager->getTheme($themeName)->getNoImage()) {
            return $this->imagineCacheManager->getBrowserPath($themeNoImagePath, $filter);
        }

        return $this->imagineCacheManager->getBrowserPath(self::DEFAULT_IMAGE, $filter);
    }

    /**
     * @return string
     */
    private function getThemeName(): string
    {
        $context = $this->contextHolder->getContext();

        return $context instanceof LayoutContext ? $context->get('theme') : '';
    }
}
