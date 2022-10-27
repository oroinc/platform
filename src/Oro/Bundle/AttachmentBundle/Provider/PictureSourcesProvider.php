<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;

/**
 * Provides value of `src` attribute that can be used in <picture> tag of an image.
 */
class PictureSourcesProvider implements PictureSourcesProviderInterface
{
    private AttachmentManager $attachmentManager;

    public function __construct(AttachmentManager $attachmentManager)
    {
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * Computes the original filtered image url for `src` attribute of the <img> tag.
     *
     * {@inheritdoc}
     */
    public function getFilteredPictureSources(?File $file, string $filterName = 'original'): array
    {
        $sources = [
            'src' => null,
            'sources' => []
        ];
        if (!$file instanceof File) {
            return $sources;
        }

        $sources['src'] = $this->attachmentManager->getFilteredImageUrl($file, $filterName);

        return $sources;
    }

    /**
     * Computes the original resized image url for `src` attribute of the <img> tag.
     *
     * {@inheritdoc}
     */
    public function getResizedPictureSources(?File $file, int $width, int $height): array
    {
        $sources = [
            'src' => null,
            'sources' => []
        ];
        if (!$file instanceof File) {
            return $sources;
        }

        $sources['src'] = $this->attachmentManager->getResizedImageUrl($file, $width, $height);

        return $sources;
    }
}
