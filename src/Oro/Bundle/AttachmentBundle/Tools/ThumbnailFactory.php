<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Model\Thumbnail;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;

class ThumbnailFactory
{
    /**
     * @var ImagineBinaryByFileContentFactoryInterface
     */
    protected $imagineBinaryFactory;

    /**
     * @var ImagineBinaryFilterInterface
     */
    protected $imagineBinaryFilter;

    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @param ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory
     * @param ImagineBinaryFilterInterface               $imagineBinaryFilter
     * @param FilterConfiguration                        $filterConfig
     */
    public function __construct(
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory,
        ImagineBinaryFilterInterface $imagineBinaryFilter,
        FilterConfiguration $filterConfig
    ) {
        $this->imagineBinaryFactory = $imagineBinaryFactory;
        $this->imagineBinaryFilter = $imagineBinaryFilter;
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

        $imagineBinary = $this->imagineBinaryFactory->createImagineBinary($content);

        $resizedImagineBinary = $this->imagineBinaryFilter->applyFilter($imagineBinary, $filter);

        return new Thumbnail(
            $resizedImagineBinary,
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
