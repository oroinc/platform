<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Default implementation of file url provider.
 */
class FileUrlProvider implements FileUrlProviderInterface
{
    private UrlGeneratorInterface $urlGenerator;

    private FilenameProviderInterface $filenameProvider;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        FilenameProviderInterface $filenameProvider
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->filenameProvider = $filenameProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileUrl(
        File $file,
        string $action = self::FILE_ACTION_GET,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate(
            'oro_attachment_get_file',
            ['id' => $file->getId(), 'filename' => $this->filenameProvider->getFileName($file), 'action' => $action],
            $referenceType
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getResizedImageUrl(
        File $file,
        int $width,
        int $height,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate(
            'oro_resize_attachment',
            [
                'id' => $file->getId(),
                'filename' => $this->filenameProvider->getResizedImageName($file, $width, $height, $format),
                'width' => $width,
                'height' => $height,
            ],
            $referenceType
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredImageUrl(
        File $file,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate(
            'oro_filtered_attachment',
            [
                'id' => $file->getId(),
                'filename' => $this->filenameProvider->getFilteredImageName($file, $filterName, $format),
                'filter' => $filterName,
                'format' => $format,
            ],
            $referenceType
        );
    }
}
