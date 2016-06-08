<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Doctrine\ORM\EntityManager;

use Gaufrette\Stream;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File as FileType;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Stream\Local as LocalStream;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileExtensionInterface;
use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class AttachmentManager
{
    const READ_COUNT = 100000;
    const DEFAULT_IMAGE_WIDTH = 100;
    const DEFAULT_IMAGE_HEIGHT = 100;
    const SMALL_IMAGE_WIDTH = 32;
    const SMALL_IMAGE_HEIGHT = 32;
    const THUMBNAIL_WIDTH  = 110;
    const THUMBNAIL_HEIGHT = 80;

    /** @var Filesystem */
    protected $filesystem;

    /** @var  Router */
    protected $router;

    /** @var  array */
    protected $fileIcons;

    /** @var ServiceLink */
    protected $securityFacadeLink;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param FilesystemMap      $filesystemMap
     * @param Router             $router
     * @param ServiceLink        $securityFacadeLink
     * @param array              $fileIcons
     * @param AssociationManager $associationManager
     */
    public function __construct(
        FilesystemMap $filesystemMap,
        Router $router,
        ServiceLink $securityFacadeLink,
        $fileIcons,
        AssociationManager $associationManager
    ) {
        $this->filesystem         = $filesystemMap->get('attachments');
        $this->router             = $router;
        $this->fileIcons          = $fileIcons;
        $this->securityFacadeLink = $securityFacadeLink;
        $this->associationManager = $associationManager;
    }

    /**
     * Copy file by $fileUrl (local path or remote file), copy it to temp dir and return Attachment entity record
     *
     * @param string $fileUrl
     * @return File|null
     */
    public function prepareRemoteFile($fileUrl)
    {
        try {
            $fileName           = pathinfo($fileUrl)['basename'];
            $parametersPosition = strpos($fileName, '?');
            if ($parametersPosition) {
                $fileName = substr($fileName, 0, $parametersPosition);
            }
            $filesystem = new SymfonyFileSystem();
            $tmpDir = ini_get('upload_tmp_dir');
            if (!$tmpDir || !is_dir($tmpDir) || !is_writable($tmpDir)) {
                $tmpDir = sys_get_temp_dir();
            }
            $tmpFile    = realpath($tmpDir) . DIRECTORY_SEPARATOR . $fileName;
            $filesystem->copy($fileUrl, $tmpFile, true);
            $file       = new FileType($tmpFile);
            $attachment = new File();
            $attachment->setFile($file);
            $this->preUpload($attachment);

            return $attachment;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update attachment entity before upload
     *
     * @param File $entity
     */
    public function preUpload(File $entity)
    {
        if ($entity->isEmptyFile()) {
            if ($entity->getFilename() !== null && $this->filesystem->has($entity->getFilename())) {
                $this->filesystem->delete($entity->getFilename());
            }
            $entity->setFilename(null);
            $entity->setExtension(null);
            $entity->setOriginalFilename(null);
        }

        if ($entity->getFile() !== null && $entity->getFile()->isFile()) {
            $entity->setOwner($this->securityFacadeLink->getService()->getLoggedUser());
            $file = $entity->getFile();
            if ($entity->getFilename() !== null && $this->filesystem->has($entity->getFilename())) {
                $this->filesystem->delete($entity->getFilename());
            }
            $entity->setExtension($file->guessExtension());

            if ($file instanceof UploadedFile) {
                $entity->setOriginalFilename($file->getClientOriginalName());
                $entity->setMimeType($file->getMimeType());
                $entity->setFileSize($file->getClientSize());
            } else {
                $entity->setOriginalFilename($file->getFileName());
                $entity->setMimeType($file->getMimeType());
                $entity->setFileSize($file->getSize());
            }

            $entity->setFilename($this->generateFileName($entity->getExtension()));

            $fsAdapter = $this->filesystem->getAdapter();
            if ($fsAdapter instanceof MetadataSupporter) {
                $fsAdapter->setMetadata(
                    $entity->getFilename(),
                    ['contentType' => $entity->getMimeType()]
                );
            }
        }
    }

    /**
     * Upload attachment file
     *
     * @param File $entity
     */
    public function upload(File $entity)
    {
        if ($entity->getFile() !== null && $entity->getFile()->isFile()) {
            $file = $entity->getFile();
            $this->copyLocalFileToStorage($file->getPathname(), $entity->getFilename());
        }
    }

    /**
     * Copy file from local filesystem to attachment storage with new name
     *
     * @param string $localFilePath
     * @param string $destinationFileName
     */
    public function copyLocalFileToStorage($localFilePath, $destinationFileName)
    {
        $srcStream = new LocalStream($localFilePath);
        $this->copyStreamToStorage($srcStream, $destinationFileName);
    }

    /**
     * Get file content
     *
     * @param File|string $file The File object or file name
     *
     * @return string
     */
    public function getContent($file)
    {
        return $this->filesystem
            ->get($file instanceof File ? $file->getFilename() : $file)
            ->getContent();
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
        $referenceType = Router::ABSOLUTE_PATH
    ) {
        return $this->router->generate(
            'oro_resize_attachment',
            [
                'width'    => $width,
                'height'   => $height,
                'id'       => $entity->getId(),
                'filename' => $entity->getOriginalFilename()
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
                'filename' => $entity->getOriginalFilename(),
                'filter'   => $filerName
            ]
        );
    }

    /**
     * if in form was clicked delete button and file has not file name - then delete this file record from the db
     *
     * @param File          $entity
     * @param EntityManager $em
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
     */
    public function copyAttachmentFile(File $file)
    {
        $fileCopy = clone $file;
        $fileCopy->setFilename($this->generateFileName($file->getExtension()));

        $sourceStream =  $this->filesystem->createStream($file->getFilename());
        $this->copyStreamToStorage($sourceStream, $fileCopy->getFilename());

        return $fileCopy;
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
            ['image/gif','image/jpeg','image/pjpeg','image/png']
        );
    }

    /**
     * @return array
     */
    public function getFileIcons()
    {
        return $this->fileIcons;
    }

    /**
     * Copy stream to storage
     *
     * @param Stream $srcStream
     * @param string $destinationFileName
     */
    protected function copyStreamToStorage(Stream $srcStream, $destinationFileName)
    {
        $dstStream = $this->filesystem->createStream($destinationFileName);

        $srcStream->open(new StreamMode('rb+'));
        $dstStream->open(new StreamMode('wb+'));

        while (!$srcStream->eof()) {
            $dstStream->write($srcStream->read(self::READ_COUNT));
        }

        $dstStream->close();
        $srcStream->close();
    }

    /**
     * Generate unique file name with specific extension
     *
     * @param string $extension
     *
     * @return string
     */
    protected function generateFileName($extension)
    {
        return sprintf('%s.%s', uniqid(), $extension);
    }
}
