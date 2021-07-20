<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;

/**
 * Layout data provider that provides path to the resized image or placeholder.
 */
class FilteredImageProvider
{
    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var ImagePlaceholderProviderInterface */
    private $imagePlaceholderProvider;

    public function __construct(
        AttachmentManager $attachmentManager,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
    }

    public function getImageUrl(?File $file, string $filter): string
    {
        if ($file) {
            return $this->attachmentManager->getFilteredImageUrl($file, $filter);
        }

        return $this->getPlaceholder($filter);
    }

    public function getPlaceholder(string $filter): string
    {
        return (string) $this->imagePlaceholderProvider->getPath($filter);
    }
}
