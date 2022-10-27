<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the image placeholder for the actual theme.
 */
class ThemeImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private LayoutContextHolder $contextHolder;

    private ThemeManager $themeManager;

    private ImagineUrlProviderInterface $imagineUrlProvider;

    private string $placeholderName;

    public function __construct(
        LayoutContextHolder $contextHolder,
        ThemeManager $themeManager,
        ImagineUrlProviderInterface $imagineUrlProvider,
        string $placeholderName
    ) {
        $this->contextHolder = $contextHolder;
        $this->themeManager = $themeManager;
        $this->imagineUrlProvider = $imagineUrlProvider;
        $this->placeholderName = $placeholderName;
    }

    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        $imagePlaceholders = $this->getImagePlaceholders();
        if (!isset($imagePlaceholders[$this->placeholderName])) {
            return null;
        }

        return $this->imagineUrlProvider
            ->getFilteredImageUrl($imagePlaceholders[$this->placeholderName], $filter, $format, $referenceType);
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
