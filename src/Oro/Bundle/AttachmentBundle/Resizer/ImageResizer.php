<?php

namespace Oro\Bundle\AttachmentBundle\Resizer;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ImageResizer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var ImagineBinaryByFileContentFactoryInterface
     */
    protected $imagineBinaryFactory;

    /**
     * @var ImagineBinaryFilterInterface
     */
    protected $imagineBinaryFilter;

    /**
     * @param FileManager                                $fileManager
     * @param ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory
     * @param ImagineBinaryFilterInterface               $imagineBinaryFilter
     */
    public function __construct(
        FileManager $fileManager,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory,
        ImagineBinaryFilterInterface $imagineBinaryFilter
    ) {
        $this->fileManager = $fileManager;
        $this->imagineBinaryFactory = $imagineBinaryFactory;
        $this->imagineBinaryFilter = $imagineBinaryFilter;
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
        $binary = $this->imagineBinaryFactory->createImagineBinary($content);

        try {
            $filteredBinary = $this->imagineBinaryFilter->applyFilter($binary, $filterName);
        } catch (RuntimeException $e) {
            if (null !== $this->logger) {
                $this->logger->warning(
                    sprintf(
                        'Image (id: %d, filename: %s) is broken. Skipped during resize.',
                        $image->getId(),
                        $image->getFilename()
                    ),
                    ['exception' => $e]
                );
            }

            return false;
        }

        return $filteredBinary;
    }
}
