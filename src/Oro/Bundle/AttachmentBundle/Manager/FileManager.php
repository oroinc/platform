<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Gaufrette\Exception\FileNotFound;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Exception\ProtocolNotSupportedException;
use Oro\Bundle\AttachmentBundle\Manager\File\TemporaryFile;
use Oro\Bundle\AttachmentBundle\Mapper\ClientMimeTypeMapperInterface;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidatorInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This manager can be used to simplify retrieving and storing attachments.
 */
class FileManager extends GaufretteFileManager
{
    private ProtocolValidatorInterface $protocolValidator;
    private ClientMimeTypeMapperInterface $clientMimeTypeMapper;
    private ExternalFileFactory $externalFileFactory;

    public function __construct(
        string $filesystemName,
        ProtocolValidatorInterface $protocolValidator,
        ClientMimeTypeMapperInterface $clientMimeTypeMapper,
        ExternalFileFactory $externalFileFactory
    ) {
        parent::__construct($filesystemName);
        $this->clientMimeTypeMapper = $clientMimeTypeMapper;
        $this->protocolValidator = $protocolValidator;
        $this->externalFileFactory = $externalFileFactory;
    }

    /**
     * Gets the content of a file.
     *
     * @param File|string $file           The File entity or file name
     * @param bool        $throwException Whether to throw exception in case the file does not exist in the storage
     *
     * @return string|null
     *
     * @throws FileNotFound When the file does not exist and throw exception is requested
     * @throws \RuntimeException When the file cannot be read
     */
    public function getContent(File|string $file, bool $throwException = true): ?string
    {
        if ($file instanceof File) {
            if ($file->getExternalUrl()) {
                // File is stored externally, it is assumed that we cannot reach its content.
                return null;
            }

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
     * @throws FileNotFoundException When the given file doesn't exist
     * @throws ProtocolNotSupportedException When the given file path is not supported
     * @throws IOException When the given file cannot be copied to a temporary folder
     */
    public function createFileEntity(string $path): File
    {
        $file = new File();

        $this->setFileFromPath($file, $path);

        return $file;
    }

    /**
     * @param File   $file The file entity for which is needed to set file property.
     * @param string $path The local path or remote URL of a file
     *
     * @throws ProtocolNotSupportedException When the given file path is not supported
     * @throws FileNotFoundException When the given file doesn't exist
     * @throws IOException When copy fails
     */
    public function setFileFromPath(File $file, string $path): void
    {
        $path = trim($path);
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

        $file->setFile(new TemporaryFile($tmpFile));
        $file->setOriginalFilename($fileName);
    }

    /**
     * @param File   $file        The file entity for which is needed to set file property.
     * @param string $externalUrl The external URL to create an {@see ExternalFile} from.
     *
     * @throws ExternalFileNotAccessibleException When the given external URL is not accessible
     */
    public function setExternalFileFromUrl(File $file, string $externalUrl): void
    {
        $externalFile = $this->externalFileFactory->createFromUrl($externalUrl);
        $file->setFile($externalFile);
        $file->setOriginalFilename($externalFile->getOriginalName() ?: $externalFile->getFilename());
    }

    /**
     * Makes a copy of the given File entity.
     */
    public function cloneFileEntity(File $file): ?File
    {
        $innerFile = $this->getFileFromFileEntity($file, false);
        if (!$innerFile) {
            return null;
        }

        $fileCopy = clone $file;
        $fileCopy->setFilename(null);
        $fileCopy->setFile($innerFile);

        return $fileCopy;
    }

    /**
     * @param File $file
     * @param bool $throwException Whether to throw exception in case the file does not exist in the storage
     *
     * @return \SplFileInfo|null
     */
    public function getFileFromFileEntity(File $file, bool $throwException = true): ?\SplFileInfo
    {
        if ($file->getExternalUrl() !== null) {
            return $this->externalFileFactory->createFromFile($file);
        }

        $content = $this->getContent($file, $throwException);
        if (null !== $content) {
            return $this->writeToTemporaryFile($content, $file->getOriginalFilename());
        }

        return null;
    }

    /**
     * Updates the given File entity before it will be uploaded to the storage.
     */
    public function preUpload(File $entity): void
    {
        if ($entity->isEmptyFile()) {
            $entity->setOriginalFilename(null);
            $entity->setMimeType(null);
            $entity->setFileSize(null);
            $entity->setExtension(null);
            $entity->setFilename($entity->getUuid());
        }

        if (!$this->isValid($entity)) {
            return;
        }

        $file = $entity->getFile();
        if ($file instanceof UploadedFile) {
            $mimeType = $this->clientMimeTypeMapper->getMimeType($file->getClientMimeType());
            $entity->setOriginalFilename($file->getClientOriginalName());
            $entity->setMimeType($mimeType);
            $entity->setExtension($file->getClientOriginalExtension());
        } elseif ($file instanceof SymfonyFile) {
            $entity->setMimeType($file->getMimeType());
            $entity->setExtension($file->guessExtension());
        } elseif ($file instanceof ExternalFile) {
            $entity->setExternalUrl($file->getUrl());
            $entity->setOriginalFilename($file->getOriginalName() ?: $file->getFilename());
            $mimeType = $this->clientMimeTypeMapper->getMimeType($file->getMimeType());
            $entity->setMimeType($mimeType);
            $entity->setExtension($file->getOriginalExtension() ?: $file->getExtension());
        } else {
            throw new \LogicException(sprintf(
                'File %s is not supported. One of the following was expected: %s',
                get_debug_type($file),
                implode(', ', [UploadedFile::class, SymfonyFile::class, ExternalFile::class])
            ));
        }

        $entity->setFileSize($file->getSize());
        $fileName = $this->generateFileName($entity->getExtension());
        while ($this->hasFile($fileName)) {
            $fileName = $this->generateFileName($entity->getExtension());
        }
        $entity->setFilename($fileName);
    }

    private function isValid(File $fileEntity): bool
    {
        $file = $fileEntity->getFile();
        if ($file instanceof ExternalFile) {
            return (bool)$file->getUrl();
        }

        return (bool)$file?->isFile();
    }

    /**
     * Uploads a file to the storage.
     */
    public function upload(File $entity): void
    {
        $file = $entity->getFile();
        if (null !== $file && $file->isFile()) {
            $this->writeFileToStorage($file->getPathname(), $entity->getFilename());
            $this->setFileMetadata($entity->getFilename(), ['contentType' => $entity->getMimeType()]);
        }
    }
}
