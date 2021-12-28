<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Represents a service to get an array of picture sources for a specified File entity.
 * This interface is used to generate <picture> tag of an image.
 */
interface PictureSourcesProviderInterface
{
    /**
     * Returns sources array that can be used in <picture> tag.
     *
     * @param File|null $file
     * @param string $filterName
     *
     * @return array
     *  [
     *      'src' => '/url/for/original/image.png',
     *      'sources' => [
     *          [
     *              'srcset' => '/url/for/formatted/image.jpg',
     *              'type' => 'image/jpg',
     *          ],
     *          // ...
     *      ],
     *  ]
     */
    public function getFilteredPictureSources(?File $file, string $filterName = 'original'): array;

    /**
     * Returns sources for the resized image that can be used in <picture> tag.
     *
     * @param File|null $file
     * @param int $width
     * @param int $height
     *
     * @return array
     *  [
     *      'src' => '/url/for/resized/image.png',
     *      'sources' => [
     *          [
     *              'srcset' => '/url/for/resized/image.jpg',
     *              'type' => 'image/jpg',
     *          ],
     *          // ...
     *      ],
     *  ]
     */
    public function getResizedPictureSources(?File $file, int $width, int $height): array;
}
