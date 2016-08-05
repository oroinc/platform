<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

use Oro\Bundle\AttachmentBundle\Model\Thumbnail;

class ThumbnailFactory
{
    /** @var ImageFactory */
    protected $imageFactory;

    /** @var FilterConfiguration */
    protected $filterConfig;

    /**
     * @param ImageFactory        $imageFactory
     * @param FilterConfiguration $filterConfig
     */
    public function __construct(ImageFactory $imageFactory, FilterConfiguration $filterConfig)
    {
        $this->imageFactory = $imageFactory;
        $this->filterConfig = $filterConfig;
    }

    /**
     * @param string $content
     * @param int    $width
     * @param int    $height
     *
     * @return Thumbnail
     */
    public function createThumbnail($content, $width, $height)
    {
        $filter = $this->getFilter($width, $height);
        $this->filterConfig->set(
            $filter,
            [
                'filters' => [
                    'thumbnail' => [
                        'size' => [$width, $height]
                    ]
                ]
            ]
        );

        return new Thumbnail(
            $this->imageFactory->createImage($content, $filter),
            $width,
            $height,
            $filter
        );
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    protected function getFilter($width, $height)
    {
        return sprintf('attachment_%s_%s', $width, $height);
    }
}
