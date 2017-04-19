<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Gaufrette\Adapter\MetadataSupporter;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

class FileManager extends GaufretteFileManager
{
    /**
     * @param FilesystemMap $filesystemMap
     * @deprecated since 2.1. Use "oro_gaufrette.file_manager" as the parent service for your service
     */
    public function __construct(FilesystemMap $filesystemMap)
    {
        parent::__construct('attachments');
        $this->setFilesystemMap($filesystemMap);
    }

    /**
     * Returns the content of a file
     *
     * @param File|string $file           The File entity or file name
     * @param bool        $throwException Whether to throw exception in case the file does not exist in the storage
     *
     * @return string|null
     */
    public function getContent($file, $throwException = true)
    {
        if ($file instanceof File) {
            $file = $file->getFilename();
        }

        return $this->getFileContent($file, $throwException);
    }

    /**
     * Copies a file to temporary directory and return File entity contains a reference to this file
     *
     * @param string $path The local path or remote URL of a file
     *
     * @return File|null
     */
    public function createFileEntity($path)
    {
        try {
            $fileName = pathinfo($path, PATHINFO_BASENAME);
            $parametersPosition = strpos($fileName, '?');
            if ($parametersPosition) {
                $fileName = substr($fileName, 0, $parametersPosition);
            }

            $tmpFile = $this->getTemporaryFileName($fileName);
            $filesystem = new SymfonyFileSystem();
            $filesystem->copy($path, $tmpFile, true);

            $entity = new File();
            $entity->setFile(new ComponentFile($tmpFile));
            $entity->setOriginalFilename($fileName);

            return $entity;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Makes a copy of File entity
     *
     * @param File $file
     *
     * @return File
     */
    public function cloneFileEntity(File $file)
    {
        $fileCopy = clone $file;
        $fileCopy->setFilename(null);

        $content = $this->getContent($file, false);
        if (null !== $content) {
            $fileCopy->setFile(
                $this->writeToTemporaryFile($content, $fileCopy->getOriginalFilename())
            );
        }

        return $fileCopy;
    }

    /**
     * Updates File entity before upload
     *
     * @param File $entity
     */
    public function preUpload(File $entity)
    {
        if ($entity->isEmptyFile()) {
            $entity->setOriginalFilename(null);
            $entity->setMimeType(null);
            $entity->setFileSize(null);
            $entity->setExtension(null);
            $entity->setFilename(null);
        }

        $file = $entity->getFile();
        if (null !== $file && $file->isFile()) {
            if ($file instanceof UploadedFile) {
                $entity->setOriginalFilename($file->getClientOriginalName());
                $entity->setMimeType($file->getClientMimeType());
                $entity->setExtension($file->getClientOriginalExtension());
            } else {
                $entity->setMimeType($file->getMimeType());
                $entity->setExtension($file->guessExtension());
            }
            $entity->setFileSize($file->getSize());
            $fileName = $this->generateFileName($entity->getExtension());
            while ($this->filesystem->has($fileName)) {
                $fileName = $this->generateFileName($entity->getExtension());
            }
            $entity->setFilename($fileName);
        }
    }

    /**
     * Uploads a file to the storage
     *
     * @param File $entity
     */
    public function upload(File $entity)
    {
        $file = $entity->getFile();
        if (null !== $file && $file->isFile()) {
            $this->writeFileToStorage($file->getPathname(), $entity->getFilename());
            $fsAdapter = $this->filesystem->getAdapter();
            if ($fsAdapter instanceof MetadataSupporter) {
                $fsAdapter->setMetadata(
                    $entity->getFilename(),
                    ['contentType' => $entity->getMimeType()]
                );
            }
        }
    }
}
