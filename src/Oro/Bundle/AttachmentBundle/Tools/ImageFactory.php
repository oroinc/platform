<?php

namespace Oro\Bundle\AttachmentBundle\Tools;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Binary\MimeTypeGuesserInterface;
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

class ImageFactory
{
    /** @var FilterManager */
    protected $filterManager;

    /** @var  MimeTypeGuesserInterface */
    protected $mimeTypeGuesser;

    /** @var  ExtensionGuesserInterface */
    protected $extensionGuesser;

    /**
     * @param FilterManager $filterManager
     * @param MimeTypeGuesserInterface $mimeTypeGuesser
     * @param ExtensionGuesserInterface $extensionGuesser
     */
    public function __construct(
        FilterManager $filterManager,
        MimeTypeGuesserInterface $mimeTypeGuesser,
        ExtensionGuesserInterface $extensionGuesser
    ) {
        $this->filterManager = $filterManager;
        $this->mimeTypeGuesser = $mimeTypeGuesser;
        $this->extensionGuesser = $extensionGuesser;
    }

    /**
     * @param string $content
     * @param string $filter
     *
     * @return BinaryInterface
     */
    public function createImage($content, $filter)
    {
        $image = $this->filterManager->applyFilter(
            $this->createBinary($content),
            $filter
        );

        return $image;
    }

    /**
     * @param string $content
     * @return Binary
     */
    protected function createBinary($content)
    {
        $mimeType = $this->mimeTypeGuesser->guess($content);
        $format = $this->extensionGuesser->guess($mimeType);

        return new Binary($content, $mimeType, $format);
    }
}
