<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Stream\Local as LocalStream;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Symfony\Component\Security\Core\Util\ClassUtils;

class AttachmentManager
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var  Router */
    protected $router;

    /** @var  array */
    protected $fileIcons;

    /**
     * @param FilesystemMap $filesystemMap
     * @param Router        $router
     * @param array         $fileIcons
     */
    public function __construct(FilesystemMap $filesystemMap, Router $router, $fileIcons)
    {
        $this->filesystem = $filesystemMap->get('attachments');
        $this->router = $router;
        $this->fileIcons = $fileIcons;
    }

    /**
     * Update attachment entity before upload
     *
     * @param Attachment $entity
     */
    public function preUpload(Attachment $entity)
    {
        if ($entity->isEmptyFile()) {
            if ($this->filesystem->has($entity->getFilename())) {
                $this->filesystem->delete($entity->getFilename());
            }
            $entity->setFilename(null);
            $entity->setExtension(null);
            $entity->setOriginalFilename(null);
        } else {
            if ($entity->getFile() !== null && $entity->getFile()->isFile()) {
                $file = $entity->getFile();
                if ($entity->getFilename() !== null && $this->filesystem->has($entity->getFilename())) {
                    $this->filesystem->delete($entity->getFilename());
                }
                $entity->setExtension($file->guessExtension());

                if ($file instanceof UploadedFile) {
                    $entity->setOriginalFilename($file->getClientOriginalName());
                    $entity->setMimeType($file->getClientMimeType());
                    $entity->setFileSize($file->getClientSize());
                } else {
                    $entity->setOriginalFilename($file->getFileName());
                    $entity->setMimeType($file->getMimeType());
                    $entity->setFileSize($file->getSize());
                }

                $entity->setFilename(uniqid() . '.' . $entity->getExtension());

                if ($this->filesystem->getAdapter() instanceof MetadataSupporter) {
                    $this->filesystem->getAdapter()->setMetadata(
                        $entity->getFilename(),
                        ['contentType' => $entity->getMimeType()]
                    );
                }
            }
        }
    }

    /**
     * Upload attachment file
     *
     * @param Attachment $entity
     */
    public function upload(Attachment $entity)
    {
        if (!$entity->isEmptyFile() && $entity->getFile() !== null && $entity->getFile()->isFile()) {
            $file = $entity->getFile();

            $src = new LocalStream($file->getPathname());
            $dst = $this->filesystem->createStream($entity->getFilename());

            $src->open(new StreamMode('rb+'));
            $dst->open(new StreamMode('wb+'));

            while (!$src->eof()) {
                $dst->write($src->read(100000));
            }
            $dst->close();
            $src->close();
        }
    }

    /**
     * Get file content
     *
     * @param Attachment $entity
     * @return string
     */
    public function getContent(Attachment $entity)
    {
        return $this->filesystem->get($entity->getFilename())->getContent();
    }

    /**
     * Get attachment url
     *
     * @param object     $parentEntity
     * @param string     $fieldName
     * @param Attachment $entity
     * @param string     $type
     * @param bool       $absolute
     * @return string
     */
    public function getAttachmentUrl($parentEntity, $fieldName, Attachment $entity, $type = 'get', $absolute = false)
    {
        $parentClass = ClassUtils::getRealClass($parentEntity);
        $urlString = base64_encode(implode(
            '/',
            [
                $parentClass,
                $fieldName,
                $parentEntity->getId(),
                $type,
                $entity->getOriginalFilename()
            ]
        ));
        return $this->router->generate(
            'oro_attachment_file',
            [
                'codedString' => $urlString,
                'extension' => $entity->getExtension()
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
     */
    public function decodeAttachmentUrl($urlString)
    {
        return explode(
            '/',
            base64_decode($urlString)
        );
    }

    /**
     * Get resized image url
     *
     * @param Attachment $entity
     * @param int        $width
     * @param int        $height
     * @return string
     */
    public function getResizedImageUrl(Attachment $entity, $width = 100, $height = 100)
    {
        return $this->router->generate(
            'oro_resize_attachment',
            [
                'width' => $width,
                'height' => $height,
                'id' => $entity->getId(),
                'filename' => $entity->getOriginalFilename()
            ]
        );
    }

    /**
     * Get filetype icon
     *
     * @param Attachment $entity
     * @return string
     */
    public function getAttachmentIconClass(Attachment $entity)
    {
        if (isset($this->fileIcons[$entity->getExtension()])) {
            return $this->fileIcons[$entity->getExtension()];
        }

        return $this->fileIcons['default'];
    }
}
