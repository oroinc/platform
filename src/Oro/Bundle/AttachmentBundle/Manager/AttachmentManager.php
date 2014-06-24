<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Stream\Local as LocalStream;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

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
    public function __construct(
        FilesystemMap $filesystemMap,
        Router $router,
        $fileIcons
    ) {
        $this->filesystem = $filesystemMap->get('attachments');
        $this->router = $router;
        $this->fileIcons = $fileIcons;
    }

    /**
     * Copy file by $fileUrl (local path or remote file), copy it to temp dir and return Attachment entity record
     *
     * @param string $fileUrl
     * @return Attachment
     */
    public function prepareRemoteFile($fileUrl)
    {
        $fileName = pathinfo($fileUrl)['basename'];
        $parametersPosition = strpos($fileName, '?');
        if ($parametersPosition) {
            $fileName = substr($fileName, 0, $parametersPosition);
        }
        $filesystem = new SymfonyFileSystem();
        $tmpFile = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . $fileName;
        $filesystem->copy($fileUrl, $tmpFile, true);
        $file = new File($tmpFile);
        $attachment = new Attachment();
        $attachment->setFile($file);
        $this->preUpload($attachment);

        return $attachment;
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
        }

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

    /**
     * Upload attachment file
     *
     * @param Attachment $entity
     */
    public function upload(Attachment $entity)
    {
        if ($entity->getFile() !== null && $entity->getFile()->isFile()) {
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
        return $this->getAttachment(
            ClassUtils::getRealClass($parentEntity),
            $parentEntity->getId(),
            $fieldName,
            $entity,
            $type = 'get',
            $absolute
        );
    }

    /**
     * Get attachment url
     *
     * @param string     $parentClass
     * @param int        $parentId
     * @param string     $fieldName
     * @param Attachment $entity
     * @param string     $type
     * @param bool       $absolute
     * @return string
     */
    public function getAttachment(
        $parentClass,
        $parentId,
        $fieldName,
        Attachment $entity,
        $type = 'get',
        $absolute = false
    ) {

        $urlString = base64_encode(
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
        );
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
     * @throws \LogicException
     */
    public function decodeAttachmentUrl($urlString)
    {
        if (!($decodedString = base64_decode($urlString)) || count($result = explode('|', $decodedString)) < 5) {
            throw new \LogicException('Input string is not correct attachment encoded parameters');
        }

        return $result;
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
