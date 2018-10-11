<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Gaufrette\Adapter\MetadataSupporter;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ProtocolNotSupportedException;
use Oro\Bundle\AttachmentBundle\Manager\File\TemporaryFile;
use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidatorInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This manager can be used to simplify retrieving and storing attachments.
 */
class FileManager extends GaufretteFileManager
{
    /** @var ProtocolValidatorInterface */
    private $protocolValidator;

    /**
     * @param FilesystemMap              $filesystemMap
     * @param ProtocolValidatorInterface $protocolValidator
     */
    public function __construct(FilesystemMap $filesystemMap, ProtocolValidatorInterface $protocolValidator)
    {
        parent::__construct('attachments');
        $this->setFilesystemMap($filesystemMap);
        $this->protocolValidator = $protocolValidator;
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
     * Copies a file to a temporary directory and returns File entity contains a reference to this file.
     *
     * @param string $path The local path or remote URL of a file
     *
     * @return File
     *
     * @throws FileNotFoundException         When the given file doesn't exist
     * @throws ProtocolNotSupportedException When the given file path is not supported
     * @throws IOException                   When the given file cannot be copied to a temporary folder
     */
    public function createFileEntity($path)
    {
        $path = \trim($path);
        $protocolDelimiter = strpos($path, '://');
        if (false !== $protocolDelimiter
            && !$this->protocolValidator->isSupportedProtocol(strtolower(substr($path, 0, $protocolDelimiter)))
        ) {
            throw new ProtocolNotSupportedException($path);
        }

        $fileName = pathinfo($path, PATHINFO_BASENAME);
        $parametersPosition = strpos($fileName, '?');
        if ($parametersPosition) {
            $fileName = substr($fileName, 0, $parametersPosition);
        }

        $tmpFile = $this->getTemporaryFileName($fileName);
        $filesystem = new SymfonyFileSystem();
        $filesystem->copy($path, $tmpFile, true);

        $entity = new File();
        $entity->setFile(new TemporaryFile($tmpFile));
        $entity->setOriginalFilename($fileName);

        return $entity;
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
