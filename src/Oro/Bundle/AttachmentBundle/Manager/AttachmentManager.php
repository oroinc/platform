<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
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

    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /** @var FileIconProvider */
    private $fileIconProvider;

    /** @var MimeTypeChecker */
    private $mimeTypeChecker;

    /** @var AssociationManager */
    private $associationManager;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param FileUrlProviderInterface $fileUrlProvider
     * @param FileIconProvider $fileIconProvider
     * @param MimeTypeChecker $mimeTypeChecker
     * @param AssociationManager $associationManager
     * @param UrlGeneratorInterface $urlGenerator
     * @param ManagerRegistry $registry
     */
    public function __construct(
        FileUrlProviderInterface $fileUrlProvider,
        FileIconProvider $fileIconProvider,
        MimeTypeChecker $mimeTypeChecker,
        AssociationManager $associationManager,
        UrlGeneratorInterface $urlGenerator,
        ManagerRegistry $registry
    ) {
        $this->fileUrlProvider = $fileUrlProvider;
        $this->fileIconProvider = $fileIconProvider;
        $this->mimeTypeChecker = $mimeTypeChecker;
        $this->associationManager = $associationManager;
        $this->urlGenerator = $urlGenerator;
        $this->registry = $registry;
    }

    /**
     * Get url of REST API resource which can be used to get the content of the given file
     *
     * @param int $fileId The id of the File object
     *
     * @return string
     */
    public function getFileRestApiUrl(int $fileId): string
    {
        return $this->urlGenerator->generate('oro_api_get_file', ['id' => $fileId, '_format' => 'binary']);
    }

    /**
     * Get file URL.
     *
     * @param File $file
     * @param string $action
     * @param int $referenceType
     *
     * @return string
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
     *
     * @param File $file
     * @param int $width
     * @param int $height
     * @param int $referenceType
     *
     * @return string
     */
    public function getResizedImageUrl(
        File $file,
        int $width = self::DEFAULT_IMAGE_WIDTH,
        int $height = self::DEFAULT_IMAGE_HEIGHT,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getResizedImageUrl($file, $width, $height, $referenceType);
    }

    /**
     * Get image attachment link with liip imagine filter applied to image
     *
     * @param File $file
     * @param string $filterName
     * @param int $referenceType
     *
     * @return string
     */
    public function getFilteredImageUrl(
        File $file,
        string $filterName,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->fileUrlProvider->getFilteredImageUrl($file, $filterName, $referenceType);
    }

    /**
     * @param int $fileId
     * @param string $filename
     * @param string $filterName
     * @param int $referenceType
     *
     * @return string
     */
    public function getFilteredImageUrlByIdAndFilename(
        int $fileId,
        string $filename,
        string $filterName,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $file = $this->getFileByIdAndFilename($fileId, $filename);
        if (!$file) {
            return '';
        }

        return $this->getFilteredImageUrl($file, $filterName, $referenceType);
    }

    /**
     * Get file type icon
     *
     * @param FileExtensionInterface $entity
     *
     * @return string
     */
    public function getAttachmentIconClass(FileExtensionInterface $entity): string
    {
        return $this->fileIconProvider->getExtensionIconClass($entity);
    }

    /**
     * Check if content type is an image
     *
     * @param string $mimeType
     *
     * @return bool
     */
    public function isImageType(string $mimeType): bool
    {
        return $this->mimeTypeChecker->isImageMimeType($mimeType);
    }

    /**
     * @return array
     */
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

    /**
     * @param int $fileId
     * @param string $filename
     *
     * @return File|null
     */
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
