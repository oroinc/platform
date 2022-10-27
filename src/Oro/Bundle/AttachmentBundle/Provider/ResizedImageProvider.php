<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Psr\Log\LoggerInterface;

/**
 * Resizes images to the specified width and height, applies filters to images.
 */
class ResizedImageProvider implements ResizedImageProviderInterface
{
    private FileManager $fileManager;

    private ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory;

    private ImagineBinaryFilterInterface $imagineBinaryFilter;

    private FilterConfiguration $filterConfig;

    private FilterRuntimeConfigProviderInterface $filterRuntimeConfigProvider;

    private LoggerInterface $logger;

    public function __construct(
        FileManager $fileManager,
        ImagineBinaryByFileContentFactoryInterface $imagineBinaryFactory,
        ImagineBinaryFilterInterface $imagineBinaryFilter,
        FilterConfiguration $filterConfig,
        FilterRuntimeConfigProviderInterface $filterRuntimeConfigProvider,
        LoggerInterface $logger
    ) {
        $this->fileManager = $fileManager;
        $this->imagineBinaryFactory = $imagineBinaryFactory;
        $this->imagineBinaryFilter = $imagineBinaryFilter;
        $this->filterConfig = $filterConfig;
        $this->filterRuntimeConfigProvider = $filterRuntimeConfigProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredImage(File $file, string $filterName, string $format = ''): ?BinaryInterface
    {
        $content = $this->getImageContent($file, $filterName);
        if (null === $content) {
            return null;
        }

        return $this->filterImage($content, $filterName, $format, $file->getFilename());
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredImageByPath(string $fileName, string $filterName, string $format = ''): ?BinaryInterface
    {
        $content = $this->getImageContentByPath($fileName, $filterName);
        if (null === $content) {
            return null;
        }

        return $this->filterImage($content, $filterName, $format, $fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredImageByContent(
        string $content,
        string $filterName,
        string $format = ''
    ): ?BinaryInterface {
        return $this->filterImage($content, $filterName, $format, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getResizedImage(File $file, int $width, int $height, string $format = ''): ?BinaryInterface
    {
        return $this->getFilteredImage(
            $file,
            $this->getResizedImageFilterName($width, $height),
            $format
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getResizedImageByPath(
        string $fileName,
        int $width,
        int $height,
        string $format = ''
    ): ?BinaryInterface {
        return $this->getFilteredImageByPath(
            $fileName,
            $this->getResizedImageFilterName($width, $height),
            $format
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getResizedImageByContent(
        string $content,
        int $width,
        int $height,
        string $format = ''
    ): ?BinaryInterface {
        return $this->getFilteredImageByContent(
            $content,
            $this->getResizedImageFilterName($width, $height),
            $format
        );
    }

    private function getResizedImageFilterName(int $width, int $height): string
    {
        $filterName = sprintf('attachment_%s_%s', $width, $height);
        $this->filterConfig->set($filterName, ['filters' => ['thumbnail' => ['size' => [$width, $height]]]]);

        return $filterName;
    }

    private function getImageContent(File $file, string $filterName): ?string
    {
        try {
            return $this->fileManager->getContent($file);
        } catch (\Exception $e) {
            $this->logException($e, $filterName, $file->getFilename());

            return null;
        }
    }

    private function getImageContentByPath(string $fileName, string $filterName): ?string
    {
        try {
            return $this->fileManager->getContent($fileName);
        } catch (\Exception $e) {
            $this->logException($e, $filterName, $fileName);

            return null;
        }
    }

    private function filterImage(
        string $content,
        string $filterName,
        string $format,
        ?string $fileName
    ): ?BinaryInterface {
        $originalImageBinary = $this->imagineBinaryFactory->createImagineBinary($content);
        try {
            $runtimeConfig = $this->filterRuntimeConfigProvider->getRuntimeConfigForFilter($filterName, $format);
            $filteredBinary = $this->imagineBinaryFilter->applyFilter(
                $originalImageBinary,
                $filterName,
                $runtimeConfig
            );
        } catch (\Exception $e) {
            $this->logger->warning(
                sprintf(
                    'Failed to apply filter %s to file %s',
                    $filterName,
                    $fileName ?? '<raw image content skipped>',
                ),
                ['exception' => $e, 'runtimeConfig' => $runtimeConfig ?? []]
            );

            return null;
        }

        return $filteredBinary;
    }

    private function logException(\Exception $exception, string $filterName, ?string $fileName): void
    {
        $this->logger->warning(
            sprintf(
                'File %s is broken. Skipped during resize to %s.',
                $fileName ?? '<raw image content skipped>',
                $filterName
            ),
            ['exception' => $exception]
        );
    }
}
