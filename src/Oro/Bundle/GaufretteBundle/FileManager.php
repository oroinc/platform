<?php

namespace Oro\Bundle\GaufretteBundle;

use Gaufrette\Exception;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\GaufretteBundle\Exception\ProtocolConfigurationException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

/**
 * This manager can be used to simplify retrieving and storing files
 * via Gaufrette filesystem abstraction layer.
 */
class FileManager
{
    /** The number of bytes to be read from a source stream at a time */
    const READ_BATCH_SIZE = 100000;

    /** @var Filesystem */
    protected $filesystem;

    /** @var string */
    private $filesystemName;

    /** @var string */
    private $protocol;

    /**
     * @param string $filesystemName The name of Gaufrette filesystem this manager works with
     */
    public function __construct($filesystemName)
    {
        $this->filesystemName = $filesystemName;
    }

    /**
     * Sets an object contains references to all declared Gaufrette filesystems.
     *
     * @param FilesystemMap $filesystemMap
     */
    public function setFilesystemMap(FilesystemMap $filesystemMap)
    {
        $this->filesystem = $filesystemMap->get($this->filesystemName);
    }

    /**
     * Sets the name of the protocol mapped to the Gaufrette stream wrapper.
     *
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * Gets the name of the protocol mapped to the Gaufrette stream wrapper.
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
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
    public function getFilePath($fileName)
    {
        if (!$this->protocol) {
            throw new ProtocolConfigurationException();
        }

        return sprintf('%s://%s/%s', $this->protocol, $this->filesystemName, $fileName);
    }

    /**
     * Finds files that name beginning with the given prefix.
     *
     * @param string $prefix
     *
     * @return string[] The names of the found files
     */
    public function findFiles($prefix = '')
    {
        $result = $this->filesystem->listKeys($prefix);
        if (!empty($result) && array_key_exists('keys', $result)) {
            $result = $result['keys'];
        }

        return $result;
    }

    /**
     * Returns a File object for the file stored in the Gaufrette file system.
     *
     * @param string $fileName
     * @param bool   $throwException Whether to throw an exception in case the file does not exist
     *                               in the Gaufrette file system
     *
     * @return File|null
     */
    public function getFile($fileName, $throwException = true)
    {
        $file = null;
        if ($throwException || $this->filesystem->has($fileName)) {
            $file = $this->filesystem->get($fileName);
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
     */
    public function getStream($fileName, $throwException = true)
    {
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
     */
    public function getFileContent($fileName, $throwException = true)
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
     */
    public function deleteFile($fileName)
    {
        if ($fileName && $this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }
    }

    /**
     * Writes the specified data to the Gaufrette file system.
     *
     * @param string $content
     * @param string $fileName
     */
    public function writeToStorage($content, $fileName)
    {
        $dstStream = $this->filesystem->createStream($fileName);
        $dstStream->open(new StreamMode('wb+'));
        try {
            $dstStream->write($content);
        } finally {
            $dstStream->close();
        }
    }

    /**
     * Copies a file from local filesystem to the Gaufrette file system.
     *
     * @param string $localFilePath
     * @param string $fileName
     */
    public function writeFileToStorage($localFilePath, $fileName)
    {
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
     */
    public function writeStreamToStorage(Stream $srcStream, $fileName, $avoidWriteEmptyStream = false)
    {
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
                    }

                    while (!$srcStream->eof()) {
                        $dstStream->write($srcStream->read(static::READ_BATCH_SIZE));
                    }
                } finally {
                    $dstStream->close();
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
    public function writeToTemporaryFile($content, $originalFileName = null)
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
     */
    public function writeStreamToTemporaryFile(Stream $srcStream, $originalFileName = null)
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
                $dstStream->close();
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
     * Generates unique file name with the given extension.
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
}
