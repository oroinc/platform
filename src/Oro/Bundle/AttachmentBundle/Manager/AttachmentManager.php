<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * General methods of working with attachments
 */
class AttachmentManager
{
    public const DEFAULT_IMAGE_WIDTH = 100;
    public const DEFAULT_IMAGE_HEIGHT = 100;
    public const SMALL_IMAGE_WIDTH = 32;
    public const SMALL_IMAGE_HEIGHT = 32;
    public const THUMBNAIL_WIDTH = 110;
    public const THUMBNAIL_HEIGHT = 80;
    public const DEFAULT_FORMAT = '';

    private FileUrlProviderInterface $fileUrlProvider;

    private FileIconProvider $fileIconProvider;

    private MimeTypeChecker $mimeTypeChecker;

    private AssociationManager $associationManager;

    private ManagerRegistry $registry;

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        FileUrlProviderInterface $fileUrlProvider,
        FileIconProvider $fileIconProvider,
        MimeTypeChecker $mimeTypeChecker,
        AssociationManager $associationManager,
        ManagerRegistry $registry,
        WebpConfiguration $webpConfiguration
    ) {
        $this->fileUrlProvider = $fileUrlProvider;
        $this->fileIconProvider = $fileIconProvider;
        $this->mimeTypeChecker = $mimeTypeChecker;
        $this->associationManager = $associationManager;
        $this->registry = $registry;
        $this->webpConfiguration = $webpConfiguration;
    }

    /**
     * Get file URL.
     */
    public function getFileUrl(
        File $file,
        string $action = FileUrlProviderInterface::FILE_ACTION_GET,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getFileUrl($file, $action, $referenceType);
    }

    /**
     * Get resized image url
     */
    public function getResizedImageUrl(
        File $file,
        int $width = self::DEFAULT_IMAGE_WIDTH,
        int $height = self::DEFAULT_IMAGE_HEIGHT,
        string $format = self::DEFAULT_FORMAT,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getResizedImageUrl($file, $width, $height, $format, $referenceType);
    }

    /**
     * Get image attachment url with LiipImagine filter applied to image
     */
    public function getFilteredImageUrl(
        File $file,
        string $filterName,
        string $format = self::DEFAULT_FORMAT,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getFilteredImageUrl($file, $filterName, $format, $referenceType);
    }

    public function isWebpEnabledIfSupported(): bool
    {
        return $this->webpConfiguration->isEnabledIfSupported();
    }

    public function isWebpEnabledForAll(): bool
    {
        return $this->webpConfiguration->isEnabledForAll();
    }

    public function isWebpDisabled(): bool
    {
        return $this->webpConfiguration->isDisabled();
    }

    public function getFilteredImageUrlByIdAndFilename(
        int $fileId,
        string $filename,
        string $filterName,
        string $format = self::DEFAULT_FORMAT,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $file = $this->getFileByIdAndFilename($fileId, $filename);
        if (!$file) {
            return '';
        }

        return $this->getFilteredImageUrl($file, $filterName, $format, $referenceType);
    }

    /**
     * Get file type icon
     */
    public function getAttachmentIconClass(FileExtensionInterface $entity): string
    {
        return $this->fileIconProvider->getExtensionIconClass($entity);
    }

    /**
     * Check if content type is an image
     */
    public function isImageType(string $mimeType): bool
    {
        return $this->mimeTypeChecker->isImageMimeType($mimeType);
    }

    public function getFileIcons(): array
    {
        return $this->fileIconProvider->getFileIcons();
    }

    /**
     * Returns the list of fields responsible to store attachment associations
     *
     * @return array [target_entity_class => field_name]
     */
    public function getAttachmentTargets(): array
    {
        return $this->associationManager->getAssociationTargets(
            AttachmentScope::ATTACHMENT,
            $this->associationManager->getSingleOwnerFilter('attachment'),
            RelationType::MANY_TO_ONE
        );
    }

    private function getFileByIdAndFilename(int $fileId, string $filename): ?File
    {
        /** @var File $file */
        $file = $this->registry->getManagerForClass(File::class)->getRepository(File::class)->find($fileId);

        if (!$file || !\in_array($filename, [$file->getFilename(), $file->getOriginalFilename()], false)) {
            return null;
        }

        return $file;
    }
}
