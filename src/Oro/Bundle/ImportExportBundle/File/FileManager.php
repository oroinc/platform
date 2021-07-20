<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Gaufrette\File;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles file manipulation logic and all related stuff such as creating path, etc.
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileManager
{
    /** @var GaufretteFileManager */
    private $gaufretteFileManager;

    public function __construct(GaufretteFileManager $gaufretteFileManager)
    {
        $this->gaufretteFileManager = $gaufretteFileManager;
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

    public function writeToStorage(string $content, string $fileName): void
    {
        $this->gaufretteFileManager->writeToStorage($content, $fileName);
    }

    public function writeToTmpLocalStorage(string $fileName): string
    {
        $content = $this->gaufretteFileManager->getFileContent($fileName);
        $pathFile = self::generateTmpFilePath($fileName);
        @file_put_contents($pathFile, $content);

        return $pathFile;
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
        $bom = pack('H*', 'EFBBBF');
        $sanitizedFileContent = preg_replace("/^$bom/", '', $fileContent);

        return is_string($sanitizedFileContent)
            ? $sanitizedFileContent
            : '';
    }
}
