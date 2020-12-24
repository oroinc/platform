<?php

namespace Oro\Bundle\GaufretteBundle;

use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Exception;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\GaufretteBundle\Exception\FlushFailedException;
use Oro\Bundle\GaufretteBundle\Exception\ProtocolConfigurationException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

/**
 * This manager can be used to simplify retrieving and storing files
 * via Gaufrette filesystem abstraction layer.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileManager
{
    /** The number of bytes to be read from a source stream at a time */
    protected const READ_BATCH_SIZE = 100000;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $filesystemName;

    /** @var string|null */
    private $subDirectory;

    /** @var bool */
    private $useSubDirectory = false;

    /** @var string */
    private $protocol;

    /**
     * @param string      $filesystemName The name of Gaufrette filesystem this manager works with
     * @param string|null $subDirectory   The name of a sub-directory if it is different than the filesystem name
     */
    public function __construct(string $filesystemName, string $subDirectory = null)
    {
        if (!$filesystemName) {
            throw new \InvalidArgumentException('The filesystem name must not be empty.');
        }
        $this->filesystemName = $filesystemName;
        if ($subDirectory) {
            $this->subDirectory = $subDirectory;
            $this->useSubDirectory = true;
        }
    }

    /**
     * Sets a flag indicates whether files should be stored in a sub-directory.
     *
     * @param bool $useSubDirectory
     */
    public function useSubDirectory(bool $useSubDirectory): void
    {
        if (!$useSubDirectory && $this->subDirectory) {
            throw new \LogicException('The Gaufrette file manager must be configured without a sub-directory.');
        }
        $this->useSubDirectory = $useSubDirectory;
    }

    /**
     * Sets an object contains references to all declared Gaufrette filesystems.
     *
     * @param FilesystemMap $filesystemMap
     */
    public function setFilesystemMap(FilesystemMap $filesystemMap): void
    {
        $this->filesystem = $filesystemMap->get($this->filesystemName);
    }

    /**
     * Sets the name of the protocol mapped to the Gaufrette stream wrapper.
     *
     * @param string $protocol
     */
    public function setProtocol(string $protocol): void
    {
        $this->protocol = $protocol;
    }

    /**
     * Gets the name of the protocol mapped to the Gaufrette stream wrapper.
     *
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @return string|null
     */
    public function getSubDirectory(): ?string
    {
        if (!$this->useSubDirectory) {
            return null;
        }

        return $this->subDirectory ?? $this->filesystemName;
    }

    /**
     * Gets the full path to a file in the Gaufrette file system.
     * This path can be used in the native file functions like "copy", "unlink", etc.
     *
     * @param string $fileName
     *
     * @return string
     *
     * @throws ProtocolConfigurationException if the Gaufrette protocol is not configured
     */
    public function getFilePath(string $fileName): string
    {
        if (!$this->protocol) {
            throw new ProtocolConfigurationException();
        }

        return sprintf(
            '%s://%s/%s',
            $this->protocol,
            $this->filesystemName,
            $this->getFileNameWithSubDirectory($fileName)
        );
    }

    /**
     * Gets the MIME type of a file in the Gaufrette file system.
     *
     * @param string $fileName
     *
     * @return string|null The MIME type or NULL if the Gaufrette file system does not support MIME types
     *                     or cannot recognize MIME type
     *
     * @throws FileNotFound if the file does not exist
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function getFileMimeType(string $fileName): ?string
    {
        try {
            return $this->filesystem->mimeType($this->getFileNameWithSubDirectory($fileName)) ?: null;
        } catch (\LogicException $e) {
            // the Gaufrette filesystem adapter does support MIME types
            return null;
        }
    }

    /**
     * Finds files that name beginning with the given prefix.
     *
     * @param string $prefix
     *
     * @return string[] The names of the found files
     */
    public function findFiles(string $prefix = ''): array
    {
        $fileNames = $this->listFiles($prefix);
        if ($fileNames && $this->getSubDirectory()) {
            $fileNamesWithoutSubDirectory = [];
            foreach ($fileNames as $fileName) {
                $fileNamesWithoutSubDirectory[] = $this->getFileNameWithoutSubDirectory($fileName);
            }
            $fileNames = $fileNamesWithoutSubDirectory;
        }

        return $fileNames;
    }

    /**
     * Checks if the given file exists in the Gaufrette file system.
     *
     * @param string $fileName
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function hasFile(string $fileName): bool
    {
        return $this->filesystem->has($this->getFileNameWithSubDirectory($fileName));
    }

    /**
     * Returns a File object for the file stored in the Gaufrette file system.
     *
     * @param string $fileName
     * @param bool   $throwException Whether to throw an exception in case the file does not exists
     *                               in the Gaufrette file system
     *
     * @return File|null
     *
     * @throws FileNotFound if the file does not exist and throw exception is requested
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function getFile(string $fileName, bool $throwException = true): ?File
    {
        $file = null;
        $fileName = $this->getFileNameWithSubDirectory($fileName);
        if ($throwException || $this->filesystem->has($fileName)) {
            $file = $this->filesystem->get($fileName);
            $file->setName($this->getFileNameWithoutSubDirectory($file->getName()));
        }

        return $file;
    }

    /**
     * Returns a File stream for the file stored in the Gaufrette file system.
     *
     * @param string $fileName       The file name
     * @param bool   $throwException Whether to throw an exception in case the file does not exists
     *
     * @return Stream|null
     *
     * @throws FileNotFound if the file does not exist and throw exception is requested
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function getStream(string $fileName, bool $throwException = true): ?Stream
    {
        $fileName = $this->getFileNameWithSubDirectory($fileName);
        $hasFile = $this->filesystem->has($fileName);
        if (!$hasFile && $throwException) {
            throw new Exception\FileNotFound($fileName);
        }

        return $hasFile
            ? $this->filesystem->createStream($fileName)
            : null;
    }

    /**
     * Returns the content of a file stored in the Gaufrette file system.
     *
     * @param string $fileName
     * @param bool   $throwException Whether to throw exception in case the file does not exist
     *                               in the Gaufrette file system
     *
     * @return string|null
     *
     * @throws FileNotFound if the file does not exist and throw exception is requested
     * @throws \RuntimeException if the file cannot be read
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function getFileContent(string $fileName, bool $throwException = true): ?string
    {
        $content = null;
        $file = $this->getFile($fileName, $throwException);
        if (null !== $file) {
            $content = $file->getContent();
        }

        return $content;
    }

    /**
     * Checks whether the given file exists and, if so, deletes it from the Gaufrette file system.
     *
     * @param string $fileName
     *
     * @throws \RuntimeException if the file cannot be deleted
     */
    public function deleteFile(string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $fileName = $this->getFileNameWithSubDirectory($fileName);
        if ($this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }
    }

    /**
     * Deletes all files from the Gaufrette file system.
     *
     * @throws \RuntimeException if any file cannot be deleted
     */
    public function deleteAllFiles(): void
    {
        $fileNames = $this->listFiles();
        foreach ($fileNames as $fileName) {
            $this->filesystem->delete($fileName);
        }
    }

    /**
     * Writes the specified data to the Gaufrette file system.
     *
     * @param string $content
     * @param string $fileName
     *
     * @throws FlushFailedException if an error occurred during the flushing data to the destination stream
     * @throws \RuntimeException if the destination stream cannot be opened
     * @throws \LogicException if the source stream does not allow read or the destination stream does not allow write
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function writeToStorage(string $content, string $fileName): void
    {
        $fileName = $this->getFileNameWithSubDirectory($fileName);
        $dstStream = $this->filesystem->createStream($fileName);
        $dstStream->open(new StreamMode('wb+'));
        try {
            $dstStream->write($content);
        } finally {
            $this->filesystem->removeFromRegister($fileName);
            $this->flushAndClose($dstStream, $fileName);
        }
    }

    /**
     * Copies a file from local filesystem to the Gaufrette file system.
     *
     * @param string $localFilePath
     * @param string $fileName
     *
     * @throws FlushFailedException if an error occurred during the flushing data to the destination stream
     * @throws \RuntimeException if the destination stream cannot be opened
     * @throws \LogicException if the source stream does not allow read or the destination stream does not allow write
     * @throws \InvalidArgumentException if the local file path of the file name is empty string
     */
    public function writeFileToStorage(string $localFilePath, string $fileName): void
    {
        if (empty($localFilePath)) {
            throw new \InvalidArgumentException('Local path is empty.');
        }
        $this->writeStreamToStorage(new LocalStream($localFilePath), $fileName);
    }

    /**
     * Writes a stream to the Gaufrette file system.
     *
     * @param Stream $srcStream
     * @param string $fileName
     * @param bool   $avoidWriteEmptyStream
     *
     * @return bool returns false in case if $avoidWriteEmptyStream = true and input stream is empty.
     *
     * @throws FlushFailedException if an error occurred during the flushing data to the destination stream
     * @throws \RuntimeException if the destination stream cannot be opened
     * @throws \LogicException if the source stream does not allow read or the destination stream does not allow write
     * @throws \InvalidArgumentException if the local file path of the file name is empty string
     */
    public function writeStreamToStorage(Stream $srcStream, string $fileName, bool $avoidWriteEmptyStream = false): bool
    {
        $fileName = $this->getFileNameWithSubDirectory($fileName);
        $srcStream->open(new StreamMode('rb'));

        $nonEmptyStream = true;
        $firstChunk = '';

        try {
            if ($avoidWriteEmptyStream) {
                // check if input stream is empty
                $firstChunk = $srcStream->read(static::READ_BATCH_SIZE);
                if ($firstChunk === '' && $srcStream->eof()) {
                    $nonEmptyStream = false;
                }
            }

            if ($nonEmptyStream) {
                $dstStream = $this->filesystem->createStream($fileName);
                $dstStream->open(new StreamMode('wb+'));
                try {
                    // save the chunk that was used to check if input stream is empty
                    if ($firstChunk) {
                        $dstStream->write($firstChunk);
                        $firstChunk = null;
                    }

                    while (!$srcStream->eof()) {
                        $dstStream->write($srcStream->read(static::READ_BATCH_SIZE));
                    }
                } finally {
                    $this->filesystem->removeFromRegister($fileName);
                    $this->flushAndClose($dstStream, $fileName);
                }
            }
        } finally {
            $srcStream->close();
        }

        return $nonEmptyStream;
    }

    /**
     * Creates a file in a temporary directory and writes the given content to it.
     *
     * @param string      $content
     * @param string|null $originalFileName
     *
     * @return ComponentFile The created temporary file
     *
     * @throws IOException if a temporary file cannot be created
     */
    public function writeToTemporaryFile(string $content, string $originalFileName = null): ComponentFile
    {
        $tmpFileName = $this->getTemporaryFileName($originalFileName);
        if (false === @file_put_contents($tmpFileName, $content)) {
            throw new IOException(sprintf('Failed to write file "%s".', $tmpFileName), 0, null, $tmpFileName);
        }

        return new ComponentFile($tmpFileName, false);
    }

    /**
     * Creates a file in a temporary directory and writes the given stream to it.
     *
     * @param Stream      $srcStream
     * @param string|null $originalFileName
     *
     * @return ComponentFile The created temporary file
     *
     * @throws FlushFailedException if an error occurred during the flushing data to the destination stream
     * @throws \RuntimeException if the destination stream cannot be opened
     * @throws \LogicException if the source stream does not allow read or the destination stream does not allow write
     */
    public function writeStreamToTemporaryFile(Stream $srcStream, string $originalFileName = null): ComponentFile
    {
        $tmpFileName = $this->getTemporaryFileName($originalFileName);
        $srcStream->open(new StreamMode('rb'));
        try {
            $dstStream = new LocalStream($tmpFileName);
            $dstStream->open(new StreamMode('wb+'));
            try {
                while (!$srcStream->eof()) {
                    $dstStream->write($srcStream->read(static::READ_BATCH_SIZE));
                }
            } finally {
                $this->flushAndClose($dstStream, $tmpFileName);
            }
        } finally {
            $srcStream->close();
        }

        return new ComponentFile($tmpFileName, false);
    }

    /**
     * Returns the full path to a new file in a temporary directory.
     *
     * @param string|null $suggestedFileName
     *
     * @return string The full path to a temporary file
     */
    public function getTemporaryFileName(string $suggestedFileName = null): string
    {
        $tmpDir = ini_get('upload_tmp_dir');
        if (!$tmpDir || !is_dir($tmpDir) || !is_writable($tmpDir)) {
            $tmpDir = sys_get_temp_dir();
        }
        $tmpDir = realpath($tmpDir);
        if (DIRECTORY_SEPARATOR !== substr($tmpDir, -\strlen(DIRECTORY_SEPARATOR))) {
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
     * Generates unique file name with the given extension.
     *
     * @param string|null $extension
     *
     * @return string
     */
    protected function generateFileName(string $extension = null): string
    {
        $fileName = str_replace('.', '', uniqid('', true));
        if ($extension) {
            $fileName .= '.' . $extension;
        }

        return $fileName;
    }

    /**
     * @param Stream $stream
     * @param string $fileName
     *
     * @throws FlushFailedException if an error occurred during the flushing data to the stream
     */
    protected function flushAndClose(Stream $stream, string $fileName): void
    {
        $success = $stream->flush();
        $stream->close();
        if (!$success) {
            throw new FlushFailedException(sprintf(
                'Failed to flush data to the "%s" file.',
                $this->getFileNameWithoutSubDirectory($fileName)
            ));
        }
    }

    /**
     * Sets a metadata for a file is stored in the Gaufrette file system.
     *
     * @param string $fileName
     * @param array  $content
     */
    protected function setFileMetadata(string $fileName, array $content): void
    {
        $adapter = $this->filesystem->getAdapter();
        if ($adapter instanceof MetadataSupporter) {
            $adapter->setMetadata($this->getFileNameWithSubDirectory($fileName), $content);
        }
    }

    /**
     * Gets names of all files are stored in the Gaufrette file system filtered by the given prefix.
     *
     * @param string $prefix
     *
     * @return string[] The list of file names with a sub-directory
     */
    private function listFiles(string $prefix = ''): array
    {
        $result = $this->filesystem->listKeys($this->getFileNameWithSubDirectory($prefix));
        if (!empty($result) && \array_key_exists('keys', $result)) {
            $result = $result['keys'];
        }

        return $result;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getFileNameWithSubDirectory(string $fileName): string
    {
        $result = ltrim($fileName, '/');

        $subDirectory = $this->getSubDirectory();
        if ($subDirectory) {
            $result = $subDirectory . '/' . $result;
        }

        return $result;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getFileNameWithoutSubDirectory(string $fileName): string
    {
        $subDirectory = $this->getSubDirectory();
        if ($subDirectory) {
            return substr($fileName, \strlen($subDirectory) + 1);
        }

        return $fileName;
    }
}
