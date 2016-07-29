<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFileSystem;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Bundle\AttachmentBundle\Entity\File;

class FileManager
{
    /** The number of bytes to be read from a source stream at a time */
    const READ_BATCH_SIZE = 100000;

    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param FilesystemMap $filesystemMap
     */
    public function __construct(FilesystemMap $filesystemMap)
    {
        $this->filesystem = $filesystemMap->get('attachments');
    }

    /**
     * Returns the content of a file
     *
     * @param File|string $file           The File object or file name
     * @param bool        $throwException Whether to throw exception in case the file does not exist in the storage
     *
     * @return string|null
     */
    public function getContent($file, $throwException = true)
    {
        $content = null;

        $fileName = $file instanceof File
            ? $file->getFilename()
            : $file;
        if ($throwException || $this->filesystem->has($fileName)) {
            $content = $this->filesystem->get($fileName)->getContent();
        }

        return $content;
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

    /**
     * Checks whether a given file exists and, if so, deletes it from the storage.
     *
     * @param string $fileName
     */
    public function deleteFile($fileName)
    {
        if ($fileName && $this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }
    }

    /**
     * Copies a file from local filesystem to the storage
     *
     * @param string $localFilePath
     * @param string $fileName
     */
    public function writeFileToStorage($localFilePath, $fileName)
    {
        $srcStream = new LocalStream($localFilePath);
        $this->writeStreamToStorage($srcStream, $fileName);
    }

    /**
     * Writes the specified data to the storage
     *
     * @param string $content
     * @param string $fileName
     */
    public function writeToStorage($content, $fileName)
    {
        $dstStream = $this->filesystem->createStream($fileName);

        $dstStream->open(new StreamMode('wb+'));
        $dstStream->write($content);
        $dstStream->close();
    }

    /**
     * Creates a file in a temporary directory and writes a given content to it
     *
     * @param string      $content
     * @param string|null $originalFileName
     *
     * @return ComponentFile The created temporary file
     *
     * @throws IOException if a temporary file cannot be created
     */
    public function writeToTemporaryFile($content, $originalFileName = null)
    {
        $tmpFileName = $this->getTemporaryFileName($originalFileName);
        if (false === @file_put_contents($tmpFileName, $content)) {
            throw new IOException(sprintf('Failed to write file "%s".', $tmpFileName), 0, null, $tmpFileName);
        }

        return new ComponentFile($tmpFileName, false);
    }

    /**
     * Returns the full path to a new file in a temporary directory
     *
     * @param string|null $suggestedFileName
     *
     * @return string The full path to a temporary file
     */
    public function getTemporaryFileName($suggestedFileName = null)
    {
        $tmpDir = ini_get('upload_tmp_dir');
        if (!$tmpDir || !is_dir($tmpDir) || !is_writable($tmpDir)) {
            $tmpDir = sys_get_temp_dir();
        }
        $tmpDir = realpath($tmpDir);
        if (DIRECTORY_SEPARATOR !== substr($tmpDir, -strlen(DIRECTORY_SEPARATOR))) {
            $tmpDir .= DIRECTORY_SEPARATOR;
        }
        $extension = null;
        if ($suggestedFileName) {
            $extension = pathinfo($suggestedFileName, PATHINFO_EXTENSION);
        }
        $tmpFile = $tmpDir . ($suggestedFileName ?: $this->generateFileName($extension));
        while (file_exists($tmpFile)) {
            $tmpFile = $tmpDir . $this->generateFileName($extension);
        }

        return $tmpFile;
    }

    /**
     * Generates unique file name with a given extension
     *
     * @param string|null $extension
     *
     * @return string
     */
    protected function generateFileName($extension = null)
    {
        $fileName = str_replace('.', '', uniqid('', true));
        if ($extension) {
            $fileName .= '.' . $extension;
        }

        return $fileName;
    }

    /**
     * Writes a stream to the storage
     *
     * @param Stream $srcStream
     * @param string $fileName
     */
    protected function writeStreamToStorage(Stream $srcStream, $fileName)
    {
        $dstStream = $this->filesystem->createStream($fileName);

        $srcStream->open(new StreamMode('rb+'));
        $dstStream->open(new StreamMode('wb+'));

        while (!$srcStream->eof()) {
            $dstStream->write($srcStream->read(self::READ_BATCH_SIZE));
        }

        $dstStream->close();
        $srcStream->close();
    }
}
