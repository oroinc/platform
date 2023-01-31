<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\LayoutContextStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the image placeholder for the actual theme.
 */
class ThemeImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private LayoutContextStack $layoutContextStack;

    private ThemeManager $themeManager;

    private ImagineUrlProviderInterface $imagineUrlProvider;

    private string $placeholderName;

    public function __construct(
        LayoutContextStack $layoutContextStack,
        ThemeManager $themeManager,
        ImagineUrlProviderInterface $imagineUrlProvider,
        string $placeholderName
    ) {
        $this->layoutContextStack = $layoutContextStack;
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
        $context = $this->layoutContextStack->getCurrentContext();
        if (!$context) {
            return [];
        }

        $themeName = $context->getOr('theme');
        if (!$themeName) {
            return [];
        }

        return $this->themeManager->getTheme($themeName)->getImagePlaceholders();
    }
}
