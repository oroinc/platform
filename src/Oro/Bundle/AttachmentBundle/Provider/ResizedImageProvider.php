<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Resizes images to the specified width and height, applies filters to images.
 */
class ResizedImageProvider implements ResizedImageProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var FileManager */
    private $fileManager;

    /** @var ImagineBinaryByFileContentFactoryInterface */
    private $imagineBinaryFactory;

    /** @var ImagineBinaryFilterInterface */
    private $imagineBinaryFilter;

    /** @var FilterConfiguration */
    private $filterConfig;

    /**
     * @param FileManager $fileManager
     * @param ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory
     * @param ImagineBinaryFilterInterface $imagineBinaryFilter
     * @param FilterConfiguration $filterConfig
     */
    public function __construct(
        FileManager $fileManager,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory,
        ImagineBinaryFilterInterface $imagineBinaryFilter,
        FilterConfiguration $filterConfig
    ) {
        $this->fileManager = $fileManager;
        $this->imagineBinaryFactory = $imagineBinaryFactory;
        $this->imagineBinaryFilter = $imagineBinaryFilter;
        $this->filterConfig = $filterConfig;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredImage($image, string $filterName): ?BinaryInterface
    {
        try {
            $rawImage = $this->fileManager->getContent($image);
        } catch (\Exception $e) {
            $rawImage = $image;
        }

        $originalImageBinary = $this->imagineBinaryFactory->createImagineBinary($rawImage);

        try {
            $filteredBinary = $this->imagineBinaryFilter->applyFilter($originalImageBinary, $filterName);
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf(
                    'File %s is broken. Skipped during resize to %s.',
                    $image === $rawImage ? '<raw image content skipped>' : (string)$image,
                    $filterName
                ),
                ['exception' => $e]
            );

            return null;
        }

        return $filteredBinary;
    }

    /**
     * {@inheritdoc}
     */
    public function getResizedImage($image, int $width, int $height): ?BinaryInterface
    {
        $filterName = sprintf('attachment_%s_%s', $width, $height);

        $this->filterConfig->set($filterName, ['filters' => ['thumbnail' => ['size' => [$width, $height]]]]);

        return $this->getFilteredImage($image, $filterName);
    }
}
