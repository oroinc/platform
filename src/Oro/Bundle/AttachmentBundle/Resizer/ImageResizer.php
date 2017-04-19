<?php

namespace Oro\Bundle\AttachmentBundle\Resizer;

use Liip\ImagineBundle\Binary\BinaryInterface;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\ImageFactory;

class ImageResizer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var ImageFactory
     */
    protected $imageFactory;

    /**
     * @param FileManager $fileManager
     * @param ImageFactory $imageFactory
     */
    public function __construct(
        FileManager $fileManager,
        ImageFactory $imageFactory
    ) {
        $this->fileManager = $fileManager;
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param File $image
     * @param string $filterName
     * @return BinaryInterface|false Filtered image or False on error
     */
    public function resizeImage(File $image, $filterName)
    {
        try {
            $content = $this->fileManager->getContent($image);
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    sprintf(
                        'Image (id: %d, filename: %s) not found. Skipped during resize.',
                        $image->getId(),
                        $image->getFilename()
                    ),
                    ['exception' => $e]
                );
            }

            return false;
        }

        return $this->imageFactory->createImage($content, $filterName);
    }
}
