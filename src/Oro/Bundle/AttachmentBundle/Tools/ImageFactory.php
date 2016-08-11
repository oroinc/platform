<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;

use Liip\ImagineBundle\Imagine\Filter\FilterManager;

class ImageFactory
{
    /** @var ImagineInterface */
    protected $imagine;

    /** @var FilterManager */
    protected $filterManager;

    /**
     * @param ImagineInterface $imagine
     * @param FilterManager    $filterManager
     */
    public function __construct(ImagineInterface $imagine, FilterManager $filterManager)
    {
        $this->imagine = $imagine;
        $this->filterManager = $filterManager;
    }

    /**
     * @param string $content
     * @param string $filter
     *
     * @return ImageInterface
     */
    public function createImage($content, $filter)
    {
        $image = $this->filterManager->applyFilter(
            $this->imagine->load($content),
            $filter
        );

        return $image;
    }
}
