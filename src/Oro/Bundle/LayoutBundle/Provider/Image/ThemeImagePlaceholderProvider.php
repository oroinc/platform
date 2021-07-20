<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the image placeholder for the actual theme.
 */
class ThemeImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    /** @var LayoutContextHolder */
    private $contextHolder;

    /** @var ThemeManager */
    private $themeManager;

    /** @var CacheManager */
    private $imagineCacheManager;

    /** @var string */
    private $placeholderName;

    public function __construct(
        LayoutContextHolder $contextHolder,
        ThemeManager $themeManager,
        CacheManager $imagineCacheManager,
        string $placeholderName
    ) {
        $this->contextHolder = $contextHolder;
        $this->themeManager = $themeManager;
        $this->imagineCacheManager = $imagineCacheManager;
        $this->placeholderName = $placeholderName;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter): ?string
    {
        $imagePlaceholders = $this->getImagePlaceholders();
        if (!isset($imagePlaceholders[$this->placeholderName])) {
            return null;
        }

        return $this->imagineCacheManager->generateUrl(
            $imagePlaceholders[$this->placeholderName],
            $filter,
            [],
            null,
            UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }

    private function getImagePlaceholders(): array
    {
        $context = $this->contextHolder->getContext();
        if (!$context instanceof LayoutContext) {
            return [];
        }

        $themeName = $context->getOr('theme');
        if (!$themeName) {
            return [];
        }

        return $this->themeManager->getTheme($themeName)
            ->getImagePlaceholders();
    }
}
