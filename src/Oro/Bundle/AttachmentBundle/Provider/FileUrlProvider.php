<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Default implementation of file url provider.
 */
class FileUrlProvider implements FileUrlProviderInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
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
            ['id' => $file->getId(), 'filename' => $file->getFilename(), 'action' => $action],
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
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate(
            'oro_resize_attachment',
            [
                'id' => $file->getId(),
                'filename' => $file->getFilename(),
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
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->urlGenerator->generate(
            'oro_filtered_attachment',
            [
                'id' => $file->getId(),
                'filename' => $file->getFilename(),
                'filter' => $filterName,
            ],
            $referenceType
        );
    }
}
