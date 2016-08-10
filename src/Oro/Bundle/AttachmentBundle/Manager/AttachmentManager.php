<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class AttachmentManager
{
    /**
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::READ_BATCH_SIZE instead
     */
    const READ_COUNT = FileManager::READ_BATCH_SIZE;

    const DEFAULT_IMAGE_WIDTH = 100;
    const DEFAULT_IMAGE_HEIGHT = 100;
    const SMALL_IMAGE_WIDTH = 32;
    const SMALL_IMAGE_HEIGHT = 32;
    const THUMBNAIL_WIDTH  = 110;
    const THUMBNAIL_HEIGHT = 80;

    /** this constant is used as a replacement of empty file name to avoid an error during URL generation */
    const UNKNOWN_FILE_NAME = 'unknown.png';

    /** @var FileManager */
    private $fileManager;

    /** @var RouterInterface */
    protected $router;

    /** @var array */
    protected $fileIcons;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param RouterInterface    $router
     * @param array              $fileIcons
     * @param AssociationManager $associationManager
     */
    public function __construct(
        RouterInterface $router,
        $fileIcons,
        AssociationManager $associationManager
    ) {
        $this->router = $router;
        $this->fileIcons = $fileIcons;
        $this->associationManager = $associationManager;
    }

    /**
     * @param FileManager $fileManager
     */
    public function setFileManager(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * Copy file by $fileUrl (local path or remote file), copy it to temp dir and return File entity record
     *
     * @param string $fileUrl
     * @return File|null
     * @deprecated since 1.10. See Oro\Bundle\AttachmentBundle\Manager\FileManager::createFileEntity
     */
    public function prepareRemoteFile($fileUrl)
    {
        try {
            $fileName = pathinfo($fileUrl, PATHINFO_BASENAME);
            $parametersPosition = strpos($fileName, '?');
            if ($parametersPosition) {
                $fileName = substr($fileName, 0, $parametersPosition);
            }

            $tmpFile = $this->fileManager->getTemporaryFileName($fileName);
            $filesystem = new SymfonyFileSystem();
            $filesystem->copy($fileUrl, $tmpFile, true);

            $entity = new File();
            $entity->setFile(new ComponentFile($tmpFile));
            $this->fileManager->preUpload($entity);
            $entity->setOriginalFilename($fileName);

            return $entity;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update attachment entity before upload
     *
     * @param File $entity
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::preUpload instead
     */
    public function preUpload(File $entity)
    {
        $this->fileManager->preUpload($entity);
    }

    /**
     * Upload attachment file
     *
     * @param File $entity
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::upload instead
     */
    public function upload(File $entity)
    {
        $this->fileManager->upload($entity);
    }

    /**
     * Copy file from local filesystem to attachment storage with new name
     *
     * @param string $localFilePath
     * @param string $destinationFileName
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::writeFileToStorage instead
     */
    public function copyLocalFileToStorage($localFilePath, $destinationFileName)
    {
        $this->fileManager->writeFileToStorage($localFilePath, $destinationFileName);
    }

    /**
     * Get file content
     *
     * @param File|string $file The File object or file name
     *
     * @return string
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::getContent instead
     */
    public function getContent($file)
    {
        return $this->fileManager->getContent($file);
    }

    /**
     * Get attachment url
     *
     * @param object $parentEntity
     * @param string $fieldName
     * @param File   $entity
     * @param string $type
     * @param bool   $absolute
     * @return string
     */
    public function getFileUrl($parentEntity, $fieldName, File $entity, $type = 'get', $absolute = false)
    {
        return $this->getAttachment(
            ClassUtils::getRealClass($parentEntity),
            $parentEntity->getId(),
            $fieldName,
            $entity,
            $type,
            $absolute
        );
    }

    /**
     * Get url of REST API resource which can be used to get the content of the given file
     *
     * @param int    $fileId           The id of the File object
     * @param string $ownerEntityClass The FQCN of an entity the File object belongs
     * @param mixed  $ownerEntityId    The id of an entity the File object belongs
     *
     * @return string
     */
    public function getFileRestApiUrl($fileId, $ownerEntityClass, $ownerEntityId)
    {
        return $this->router->generate(
            'oro_api_get_file',
            [
                'key' => $this->buildFileKey($fileId, $ownerEntityClass, $ownerEntityId),
                '_format' => 'binary'
            ]
        );
    }

    /**
     * Get human readable file size
     *
     * @param integer $bytes
     * @return string
     */
    public function getFileSize($bytes)
    {
        $sz = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        $key = (int)$factor;

        return isset($sz[$key]) ? sprintf("%.2f", $bytes / pow(1000, $factor)) . ' ' . $sz[$key] : $bytes;
    }

    /**
     * Get attachment url
     *
     * @param string $parentClass
     * @param int    $parentId
     * @param string $fieldName
     * @param File   $entity
     * @param string $type
     * @param bool   $absolute
     * @return string
     */
    public function getAttachment(
        $parentClass,
        $parentId,
        $fieldName,
        File $entity,
        $type = 'get',
        $absolute = false
    ) {
        $urlString = str_replace(
            '/',
            '_',
            base64_encode(
                implode(
                    '|',
                    [
                        $parentClass,
                        $fieldName,
                        $parentId,
                        $type,
                        $entity->getOriginalFilename()
                    ]
                )
            )
        );
        return $this->router->generate(
            'oro_attachment_file',
            [
                'codedString' => $urlString,
                'extension'   => $entity->getExtension()
            ],
            $absolute
        );
    }

    /**
     * Return url parameters from encoded string
     *
     * @param $urlString
     * @return array
     *   - parent class
     *   - field name
     *   - entity id
     *   - download type
     *   - original filename
     * @throws \LogicException
     */
    public function decodeAttachmentUrl($urlString)
    {
        if (!($decodedString = base64_decode(str_replace('_', '/', $urlString)))
            || count($result = explode('|', $decodedString)) < 5
        ) {
            throw new \LogicException('Input string is not correct attachment encoded parameters');
        }

        return $result;
    }

    /**
     * Get resized image url
     *
     * @param File        $entity
     * @param int         $width
     * @param int         $height
     * @param bool|string $referenceType
     *
     * @return string
     */
    public function getResizedImageUrl(
        File $entity,
        $width = self::DEFAULT_IMAGE_WIDTH,
        $height = self::DEFAULT_IMAGE_HEIGHT,
        $referenceType = RouterInterface::ABSOLUTE_PATH
    ) {
        return $this->router->generate(
            'oro_resize_attachment',
            [
                'width'    => $width,
                'height'   => $height,
                'id'       => $entity->getId(),
                'filename' => $entity->getFilename() ?: self::UNKNOWN_FILE_NAME
            ],
            $referenceType
        );
    }

    /**
     * Get filetype icon
     *
     * @param FileExtensionInterface $entity
     * @return string
     */
    public function getAttachmentIconClass(FileExtensionInterface $entity)
    {
        return isset($this->fileIcons[$entity->getExtension()])
            ? $this->fileIcons[$entity->getExtension()]
            : $this->fileIcons['default'];
    }

    /**
     * Get image attachment link with liip imagine filter applied to image
     *
     * @param File   $entity
     * @param string $filerName
     * @return string
     */
    public function getFilteredImageUrl(File $entity, $filerName)
    {
        return $this->router->generate(
            'oro_filtered_attachment',
            [
                'id'       => $entity->getId(),
                'filename' => $entity->getFilename() ?: self::UNKNOWN_FILE_NAME,
                'filter'   => $filerName
            ]
        );
    }

    /**
     * if in form was clicked delete button and file has not file name - then delete this file record from the db
     *
     * @param File          $entity
     * @param EntityManager $em
     * @deprecated since 1.10. This method is never used and will be removed
     */
    public function checkOnDelete(File $entity, EntityManager $em)
    {
        if ($entity->isEmptyFile() && $entity->getFilename() === null) {
            $em->remove($entity);
        }
    }

    /**
     * Builds the key of the File object
     *
     * @param int    $fileId           The id of the File object
     * @param string $ownerEntityClass The FQCN of an entity the File object belongs
     * @param mixed  $ownerEntityId    The id of an entity the File object belongs
     *
     * @return string
     */
    public function buildFileKey($fileId, $ownerEntityClass, $ownerEntityId)
    {
        return str_replace(
            '/',
            '_',
            base64_encode(serialize([$fileId, $ownerEntityClass, $ownerEntityId]))
        );
    }

    /**
     * Extracts data from the given key of the File object
     *
     * @param string $key
     *
     * @return array [fileId, ownerEntityClass, ownerEntityId]
     *
     * @throws \InvalidArgumentException
     */
    public function parseFileKey($key)
    {
        if (!($decoded = base64_decode(str_replace('_', '/', $key)))
            || count($result = @unserialize($decoded)) !== 3
        ) {
            throw new \InvalidArgumentException(sprintf('Invalid file key: "%s".', $key));
        }

        return $result;
    }

    /**
     * Returns the list of fields responsible to store attachment associations
     *
     * @return array [target_entity_class => field_name]
     */
    public function getAttachmentTargets()
    {
        return $this->associationManager->getAssociationTargets(
            AttachmentScope::ATTACHMENT,
            $this->associationManager->getSingleOwnerFilter('attachment'),
            RelationType::MANY_TO_ONE
        );
    }

    /**
     * Copy attachment file object
     *
     * @param File $file
     *
     * @return File
     * @deprecated since 1.10. Use Oro\Bundle\AttachmentBundle\Manager\FileManager::cloneFileEntity instead
     */
    public function copyAttachmentFile(File $file)
    {
        return $this->fileManager->cloneFileEntity($file);
    }
    
    /**
     * Check if content type is an image
     *
     * @param string $contentType
     * @return bool
     */
    public function isImageType($contentType)
    {
        return in_array(
            $contentType,
            ['image/gif','image/jpeg','image/pjpeg','image/png'],
            true
        );
    }

    /**
     * @return array
     */
    public function getFileIcons()
    {
        return $this->fileIcons;
    }
}
