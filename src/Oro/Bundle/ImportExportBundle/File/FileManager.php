<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Gaufrette\File;
use Gaufrette\Stream;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles file manipulation logic and all related stuff such as creating path, etc.
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FileManager
{
    /** @var GaufretteFileManager */
    private $gaufretteFileManager;

    /** @var resource[] */
    private $tempFileHandles = [];

    public function __construct(GaufretteFileManager $gaufretteFileManager)
    {
        $this->gaufretteFileManager = $gaufretteFileManager;
    }

    public function __destruct()
    {
        foreach ($this->tempFileHandles as $tempFileHandle) {
            if (\is_resource($tempFileHandle)) {
                @fclose($tempFileHandle);
            }
        }
    }

    /**
     * Saves the given file in a temporary directory and returns its name
     */
    public function saveImportingFile(UploadedFile $file): string
    {
        $tmpFileName = self::generateUniqueFileName($file->getClientOriginalExtension());
        $this->saveFileToStorage($file, $tmpFileName);

        return $tmpFileName;
    }

    /**
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     *
     * @return array [file name => Gaufrette\File, ...]
     */
    public function getFilesByPeriod(\DateTime $from = null, \DateTime $to = null): array
    {
        $files = [];
        foreach ($this->gaufretteFileManager->findFiles() as $fileName) {
            if (!$this->gaufretteFileManager->hasFile($fileName)) {
                continue;
            }

            $file = $this->gaufretteFileManager->getFile($fileName);
            $mtime = $file->getMtime();
            $mDateTime = new \DateTime();
            $mDateTime->setTimestamp($mtime);

            if ($from && $mDateTime < $from) {
                continue;
            }
            if ($to && $mDateTime > $to) {
                continue;
            }

            $files[$fileName] = $file;
        }

        return $files;
    }

    public function saveFileToStorage(\SplFileInfo $file, string $fileName): void
    {
        $this->gaufretteFileManager->writeToStorage(
            $this->removeByteOrderMark($file->openFile()->fread($file->getSize())),
            $fileName
        );
    }

    public function writeFileToStorage(string $localFilePath, string $fileName): void
    {
        $this->gaufretteFileManager->writeToStorage(
            $this->removeByteOrderMark(@file_get_contents($localFilePath, 'r')),
            $fileName
        );
    }

    public function copyFileToStorage(string $localFilePath, string $fileName): void
    {
        $stream = new LocalStream($localFilePath);
        try {
            $stream->open(new StreamMode('rb'));
        } catch (\RuntimeException) {
            return;
        }

        $this->skipByteOrderMarkInStream($stream);
        $this->gaufretteFileManager->writeStreamToStorage($stream, $fileName);
    }

    public function writeToStorage(string $content, string $fileName): void
    {
        $this->gaufretteFileManager->writeToStorage($content, $fileName);
    }

    public function writeToTmpLocalStorage(string $fileName): string
    {
        $content = $this->gaufretteFileManager->getFileContent($fileName);

        $tempFilePath = $this->createTmpFile();
        @file_put_contents($tempFilePath, $content);

        return $tempFilePath;
    }

    public function createTmpFile(): string
    {
        $tempFileHandle = tmpfile();
        if (!$tempFileHandle) {
            throw new \RuntimeException('Cannot create a temporary file.');
        }
        $this->tempFileHandles[] = $tempFileHandle;

        return stream_get_meta_data($tempFileHandle)['uri'];
    }

    public function deleteTmpFile(string $filePath): void
    {
        $foundTmpFileKey = null;
        foreach ($this->tempFileHandles as $key => $tempFileHandle) {
            if (\is_resource($tempFileHandle) && stream_get_meta_data($tempFileHandle)['uri'] === $filePath) {
                $foundTmpFileKey = $key;
            }
        }
        if (null !== $foundTmpFileKey) {
            $foundTmpFileHandle = $this->tempFileHandles[$foundTmpFileKey];
            if (\is_resource($foundTmpFileHandle)) {
                @fclose($foundTmpFileHandle);
            }
            unset($this->tempFileHandles[$foundTmpFileKey]);
        }
    }

    /**
     * As ini_set('auto_detect_line_endings', true); does not fix problem of line endings in the file we forced to
     * explicitly replace all possible `new lines` to one common
     * It is possible to replace it on field level (CsvFileStreamWriter::writeLine()) but it will be overhead because
     * it is called several times for each row in file
     */
    public function fixNewLines(string $file): string
    {
        $allContent = file_get_contents($file);
        file_put_contents($file, preg_replace('~\R~u', PHP_EOL, $allContent));

        return $file;
    }

    /**
     * Generates unique file name with a given extension
     */
    public static function generateUniqueFileName(string $extension = null): string
    {
        $fileName = str_replace('.', '', uniqid('', true));
        if ($extension) {
            $fileName .= '.' . $extension;
        }

        return $fileName;
    }

    public static function generateTmpFilePath(string $fileName): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Generates file name with a current date
     */
    public static function generateFileName(string $prefix, string $extension): string
    {
        $filePrefix = sprintf('%s_%s', $prefix, date('Y_m_d_H_i_s'));

        return sprintf(
            '%s.%s',
            preg_replace('~\W~', '_', uniqid($filePrefix . '_')),
            $extension
        );
    }

    public function getFilePath(string $fileName): string
    {
        return $this->gaufretteFileManager->getFilePath($fileName);
    }

    /**
     * @param string|File $file
     *
     * @return string
     */
    public function getContent($file): string
    {
        if (!$file instanceof File) {
            $file = $this->gaufretteFileManager->getFile($file);
        }

        return $file->getContent();
    }

    /**
     * @param string|File $file
     */
    public function deleteFile($file): void
    {
        if ($file instanceof File) {
            $file = $file->getName();
        }

        if ($file) {
            $this->gaufretteFileManager->deleteFile($file);
        }
    }

    public function isFileExist(string $fileName): bool
    {
        return $this->gaufretteFileManager->hasFile($fileName);
    }

    /**
     * @param string|File $file
     *
     * @return string|null
     */
    public function getMimeType($file): ?string
    {
        if ($file instanceof File) {
            $file = $file->getName();
        }

        return $file && $this->gaufretteFileManager->hasFile($file)
            ? $this->gaufretteFileManager->getFileMimeType($file)
            : null;
    }

    public function getFilesByFilePattern(string $pattern): array
    {
        $result = [];
        foreach ($this->gaufretteFileManager->findFiles() as $fileName) {
            if (fnmatch($pattern, $fileName)) {
                $result[] = $fileName;
            }
        }

        return $result;
    }

    /**
     * Removes byte order mark (BOM) from the beginning of the file.
     */
    private function removeByteOrderMark(string $fileContent): string
    {
        $bom = $this->getBomString();
        $sanitizedFileContent = preg_replace("/^$bom/", '', $fileContent);

        return is_string($sanitizedFileContent)
            ? $sanitizedFileContent
            : '';
    }

    /**
     * This process ensures that BOM at the beginning of the stream is skipped.
     */
    private function skipByteOrderMarkInStream(Stream $stream): void
    {
        $bom = $this->getBomString();
        $bomSize = strlen($bom);
        $startBytes = $stream->read($bomSize);
        if ($startBytes !== $bom) {
            $stream->seek(0);
        }
    }

    private function getBomString(): string
    {
        return pack('H*', 'EFBBBF');
    }

    public function getFileSize(string $fileName): int
    {
        $filePath = $this->getFilePath($fileName);

        if (!$this->isFileExist($filePath)) {
            return 0;
        }

        return filesize($filePath);
    }
}
