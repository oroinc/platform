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
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
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

    private const DIRECTORY_SEPARATOR = '/';

    /** @var array */
    private $tmpFiles = [];

    /** @var bool */
    private $clearTempFilesCallbackRegistered = false;

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

    /** @var string */
    private $readonlyProtocol;

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

    public function __destruct()
    {
        $this->clearTempFiles();
    }

    public function clearTempFiles(): void
    {
        foreach (array_keys($this->tmpFiles) as $tmpFile) {
            @unlink($tmpFile);
        }

        $this->tmpFiles = [];
    }

    private function rememberTempFile(string $file): void
    {
        if (!$this->clearTempFilesCallbackRegistered) {
            // In case of unexpected exit(); call or maximum execution time reaching temp files should be removed
            register_shutdown_function([$this, 'clearTempFiles']);
            $this->clearTempFilesCallbackRegistered = true;
        }

        $this->tmpFiles[$file] = true;
    }

    /**
     * Sets a flag indicates whether files should be stored in a sub-directory.
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
     */
    public function setFilesystemMap(FilesystemMap $filesystemMap): void
    {
        $this->filesystem = $filesystemMap->get($this->filesystemName);
    }

    /**
     * Sets the name of the protocol mapped to the Gaufrette stream wrapper.
     */
    public function setProtocol(string $protocol): void
    {
        $this->protocol = $protocol;
    }

    /**
     * Sets the name of the read-only protocol mapped to the Gaufrette stream wrapper.
     */
    public function setReadonlyProtocol(string $protocol): void
    {
        $this->readonlyProtocol = $protocol;
    }

    /**
     * Gets the name of the protocol mapped to the Gaufrette stream wrapper.
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * Gets the name of the read-only protocol mapped to the Gaufrette stream wrapper.
     */
    public function getReadonlyProtocol(): string
    {
        return $this->readonlyProtocol;
    }

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
     * @throws ProtocolConfigurationException if the Gaufrette protocol is not configured
     */
    public function getFilePath(string $fileName): string
    {
        if (!$this->protocol) {
            throw new ProtocolConfigurationException();
        }

        return $this->protocol . '://' . $this->getFilePathWithoutProtocol($fileName);
    }

    /**
     * Gets the full path to a file in the read-only Gaufrette file system.
     * This path can be used in the native file functions that need a read-only access to a file.
     *
     * @throws ProtocolConfigurationException if the Gaufrette protocol is not configured
     */
    public function getReadonlyFilePath(string $fileName): string
    {
        if (!$this->readonlyProtocol) {
            throw new ProtocolConfigurationException();
        }

        return $this->readonlyProtocol . '://' . $this->getFilePathWithoutProtocol($fileName);
    }

    /**
     * Gets the full path to a file in the Gaufrette file system but without the protocol part.
     * For example, if a full path is "gaufrette://my_filesystem/file.txt",
     * the full full path the protocol part is "my_filesystem/file.txt".
     */
    public function getFilePathWithoutProtocol(string $fileName): string
    {
        return
            $this->filesystemName
            . self::DIRECTORY_SEPARATOR
            . $this->getFileNameWithSubDirectory($fileName);
    }

    /**
     * Returns a human readable representation of a Gaufrette adapter used by this file manager:
     * * for Local adapter the full path to the directory will be returned.
     * * for other adapters the name of the adapter will be returned.
     */
    public function getAdapterDescription(): string
    {
        $directory = $this->getLocalPath();
        if (null !== $directory) {
            return $directory;
        }

        $adapter = $this->filesystem->getAdapter();
        $className = \get_class($adapter);
        $lastSeparatorPos = strrpos($className, '\\');
        if (false !== $lastSeparatorPos) {
            $className = substr($className, $lastSeparatorPos + 1);
        }

        return $className;
    }

    /**
     * Returns the full path to the directory for Local adapter.
     */
    public function getLocalPath(): ?string
    {
        $adapter = $this->filesystem->getAdapter();

        if (!$adapter instanceof LocalAdapter) {
            return null;
        }

        $directory = $adapter->getDirectory();
        $subDirectory = $this->getSubDirectory();
        if ($subDirectory) {
            $directory .= DIRECTORY_SEPARATOR . $subDirectory;
        }

        return $directory;
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
     */
    public function getFileMimeType(string $fileName): ?string
    {
        if (!$fileName) {
            return null;
        }

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
        $fileNames = $this->listKeys($this->getFileNameWithSubDirectory($prefix))['keys'];
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
     */
    public function hasFile(string $fileName): bool
    {
        if (!$fileName) {
            return false;
        }

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
     */
    public function getFile(string $fileName, bool $throwException = true): ?File
    {
        if (!$fileName) {
            return null;
        }

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
     */
    public function getStream(string $fileName, bool $throwException = true): ?Stream
    {
        if (!$fileName) {
            return null;
        }

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
     * Deletes the given file from the Gaufrette file system if it exists.
     *
     * @throws \RuntimeException if the file cannot be deleted
     */
    public function deleteFile(string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        // delete temp file created by file manager
        if (\in_array($fileName, $this->tmpFiles, true)) {
            unset($this->tmpFiles[$fileName]);
            @unlink($fileName);

            return;
        }

        $realFileName = $this->getFileNameWithSubDirectory($fileName);
        if ($this->filesystem->has($realFileName) && !$this->filesystem->isDirectory($realFileName)) {
            $this->filesystem->delete($realFileName);

            // delete all parent directories that do not contain any files or sub-directories
            $dirName = $this->getDirectoryName($fileName);
            while ($dirName) {
                $realDirName = $this->getFileNameWithSubDirectory($dirName);
                if ($this->filesystem->isDirectory($realDirName) && $this->isDirectoryEmpty($realDirName)) {
                    $this->deleteDirectory($realDirName);
                    $dirName = $this->getDirectoryName($dirName);
                } else {
                    break;
                }
            }
        }
    }

    /**
     * Deletes all files that name beginning with the given prefix from the Gaufrette file system.
     *
     * @throws \RuntimeException if any file cannot be deleted
     */
    public function deleteAllFiles(string $prefix = ''): void
    {
        $hasTailingDirectorySeparator = str_ends_with($prefix, self::DIRECTORY_SEPARATOR);
        if ($hasTailingDirectorySeparator) {
            $prefix = rtrim($prefix, self::DIRECTORY_SEPARATOR) . self::DIRECTORY_SEPARATOR;
        }
        $realDirName = $this->getFileNameWithSubDirectory($prefix);
        $listResult = $this->listKeys($realDirName);
        if (empty($listResult['keys']) && empty($listResult['dirs'])) {
            return;
        }

        foreach ($listResult['keys'] as $fileName) {
            $this->filesystem->delete($fileName);
        }
        foreach ($listResult['dirs'] as $dirName) {
            $this->deleteDirectory($dirName);
        }
        if ($hasTailingDirectorySeparator) {
            $dirName = substr($prefix, 0, -\strlen(self::DIRECTORY_SEPARATOR));
            if ($dirName) {
                $dirName = $this->getFileNameWithSubDirectory($dirName);
                if ($this->filesystem->isDirectory($dirName)) {
                    $this->deleteDirectory($dirName);
                }
            }
        }
    }

    /**
     * Writes the specified data to the Gaufrette file system.
     *
     * @throws FlushFailedException if an error occurred during the flushing data to the destination stream
     * @throws \RuntimeException if the destination stream cannot be opened
     * @throws \LogicException if the source stream does not allow read or the destination stream does not allow write
     * @throws \InvalidArgumentException if the file name is empty string
     */
    public function writeToStorage(string $content, string $fileName): void
    {
        if (!$fileName) {
            throw new \InvalidArgumentException('The file name must not be empty.');
        }

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
     * @throws FlushFailedException if an error occurred during the flushing data to the destination stream
     * @throws \RuntimeException if the destination stream cannot be opened
     * @throws \LogicException if the source stream does not allow read or the destination stream does not allow write
     * @throws \InvalidArgumentException if the local file path or the file name are empty string
     */
    public function writeFileToStorage(string $localFilePath, string $fileName): void
    {
        if (!$localFilePath) {
            throw new \InvalidArgumentException('The local path must not be empty.');
        }
        if (!$fileName) {
            throw new \InvalidArgumentException('The file name must not be empty.');
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
        if (!$fileName) {
            throw new \InvalidArgumentException('The file name must not be empty.');
        }

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
        $this->rememberTempFile($tmpFileName);

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
        $this->rememberTempFile($tmpFileName);

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
        $tmpDir = $this->getTempDirPath();
        $extension = null;
        if ($suggestedFileName) {
            $extension = pathinfo($suggestedFileName, PATHINFO_EXTENSION);
            $suggestedFileName = substr(
                preg_replace('/[<>:\"\/|?*\\\[:cntrl:]]+/', '_', basename($suggestedFileName)),
                -200
            );
        }
        $tmpFile = $tmpDir . ($suggestedFileName ?: $this->generateFileName($extension));
        while (file_exists($tmpFile)) {
            $tmpFile = $tmpDir . $this->generateFileName($extension);
        }

        return $tmpFile;
    }

    /**
     * Generates unique file name with the given extension.
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
     * @throws FlushFailedException if an error occurred during the flushing data to the stream
     */
    protected function flushAndClose(Stream $stream, string $fileName): void
    {
        $success = $stream->flush();
        $stream->close();
        if (!$success) {
            throw new FlushFailedException(sprintf(
                'Failed to flush data to the "%s" file.',
                $fileName
            ));
        }
    }

    /**
     * Sets a metadata for a file is stored in the Gaufrette file system.
     */
    protected function setFileMetadata(string $fileName, array $content): void
    {
        $adapter = $this->filesystem->getAdapter();
        if ($adapter instanceof MetadataSupporter) {
            $adapter->setMetadata($this->getFileNameWithSubDirectory($fileName), $content);
        }
    }

    /**
     * @param string $prefix
     *
     * @return array ['keys' => [file key, ...], 'dirs' => [dir key, ...]]
     */
    private function listKeys(string $prefix): array
    {
        $result = $this->filesystem->listKeys($prefix);
        $hasDirs = \array_key_exists('dirs', $result);
        if (\array_key_exists('keys', $result)) {
            if (!$hasDirs) {
                $result['dirs'] = [];
            }
        } elseif ($hasDirs) {
            $result['keys'] = [];
        } else {
            $result = ['keys' => $result, 'dirs' => []];
        }

        return $result;
    }

    /**
     * Checks if the given directory does not contain any files and sub-directories.
     */
    private function isDirectoryEmpty(string $realDirName): bool
    {
        $listResult = $this->listKeys($realDirName . self::DIRECTORY_SEPARATOR);

        return empty($listResult['keys']) && empty($listResult['dirs']);
    }

    /**
     * Deletes a directory from the Gaufrette file system.
     */
    private function deleteDirectory(string $realDirName): void
    {
        // use the adapter due to Filesystem::delete() is able to delete files only
        $this->filesystem->getAdapter()->delete($realDirName);
    }

    /**
     * Gets a directory where the given file is located.
     */
    private function getDirectoryName(string $fileName): string
    {
        $normalizedFileName = ltrim($fileName, self::DIRECTORY_SEPARATOR);
        $lastPos = strrpos($normalizedFileName, self::DIRECTORY_SEPARATOR);

        return false !== $lastPos
            ? substr($normalizedFileName, 0, $lastPos)
            : '';
    }

    private function getFileNameWithSubDirectory(string $fileName): string
    {
        $result = ltrim($fileName, self::DIRECTORY_SEPARATOR);

        $subDirectory = $this->getSubDirectory();
        if ($subDirectory) {
            $result = $subDirectory . self::DIRECTORY_SEPARATOR . $result;
        }

        return $result;
    }

    private function getFileNameWithoutSubDirectory(string $fileName): string
    {
        $subDirectory = $this->getSubDirectory();
        if ($subDirectory) {
            return substr($fileName, \strlen($subDirectory) + 1);
        }

        return $fileName;
    }

    private function getTempDirPath(): string
    {
        $tmpDir = ini_get('upload_tmp_dir');
        if (!$tmpDir || !is_dir($tmpDir) || !is_writable($tmpDir)) {
            $tmpDir = sys_get_temp_dir();
        }

        $tmpDir = realpath($tmpDir);
        if (!str_ends_with($tmpDir, DIRECTORY_SEPARATOR)) {
            $tmpDir .= DIRECTORY_SEPARATOR;
        }

        return $tmpDir;
    }
}
