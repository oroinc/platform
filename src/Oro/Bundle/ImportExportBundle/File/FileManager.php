<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Gaufrette\Adapter;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Handles file manipulation logic and all related stuff such as creating path, etc.
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FileManager
{
    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param FilesystemMap $filesystemMap
     */
    public function __construct(FilesystemMap $filesystemMap)
    {
        $this->filesystem = $filesystemMap->get('importexport');
    }

    /**
     * Saves the given file in a temporary directory and returns its name
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    public function saveImportingFile(UploadedFile $file)
    {
        $tmpFileName = self::generateUniqueFileName($file->getClientOriginalExtension());
        $this->saveFileToStorage($file, $tmpFileName);

        return $tmpFileName;
    }

    /**
     * @param File|string $file
     * @return null|int
     */
    public function getModifyDataFile($file)
    {
        if (! $file instanceof File) {
            $file = $this->filesystem->get($file);
        }

        if (! $file instanceof File) {
            return null;
        }

        $file->getMtime();
    }


    /**
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->filesystem->getAdapter();
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array [fileName => File]
     */
    public function getFilesByPeriod(\DateTime $from = null, \DateTime $to = null)
    {
        $files = [];

        foreach ($this->filesystem->keys() as $fileName) {
            if (($file = $this->filesystem->get($fileName)) instanceof File) {
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
        }

        return $files;
    }

    /**
     * @param \SplFileInfo $file
     * @param string $fileName
     * @param bool $overwrite
     */
    public function saveFileToStorage(\SplFileInfo $file, $fileName, $overwrite = false)
    {
        $this->filesystem->write($fileName, $file->openFile()->fread($file->getSize()), $overwrite);
    }

    /**
     * @param string $localFilePath
     * @param string $fileName
     * @param boolean $overwrite
     */
    public function writeFileToStorage($localFilePath, $fileName, $overwrite = false)
    {
        $this->filesystem->write($fileName, @file_get_contents($localFilePath, 'r'), $overwrite);
    }

    /**
     * @return Filesystem
     */
    public function getFileSystem()
    {
        return $this->filesystem;
    }

    /**
     * @param string $fileName
     * @return string
     */
    public function writeToTmpLocalStorage($fileName)
    {
        $content = $this->filesystem->read($fileName);
        $pathFile = self::generateTmpFilePath($fileName);
        @file_put_contents($pathFile, $content);

        return $pathFile;
    }

    /** As ini_set('auto_detect_line_endings', true); does not fix problem of line endings in the file we forced to
     * explicitly replace all possible `new lines` to one common
     * It is possible to replace it on field level (CsvFileStreamWriter::writeLine()) but it will be overhead because
     * it is called several times for each row in file
     * @param string $file
     * @return string
     */
    public function fixNewLines($file)
    {
        $allContent = file_get_contents($file);
        file_put_contents($file, preg_replace('~\R~u', PHP_EOL, $allContent));

        return $file;
    }

    /**
     * Generates unique file name with a given extension
     *
     * @param string|null $extension
     *
     * @return string
     */
    public static function generateUniqueFileName($extension = null)
    {
        $fileName = str_replace('.', '', uniqid('', true));
        if ($extension) {
            $fileName .= '.' . $extension;
        }

        return $fileName;
    }

    /**
     * @param string $fileName
     * @return string
     */
    public static function generateTmpFilePath($fileName)
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Generates file name with a current date
     *
     * @param string $prefix
     * @param string $extension
     * @return string
     */
    public static function generateFileName($prefix, $extension)
    {
        $filePrefix = sprintf('%s_%s', $prefix, date('Y_m_d_H_i_s'));

        return sprintf(
            '%s.%s',
            preg_replace('~\W~', '_', uniqid($filePrefix . '_')),
            $extension
        );
    }

    /**
     * @param string|File $file
     * @return string
     */
    public function getContent($file)
    {
        if (!$file instanceof File) {
            $file = $this->filesystem->get($file);
        }

        return $file->getContent();
    }

    /**
     * @param string|File $file
     */
    public function deleteFile($file)
    {
        if ($file instanceof File) {
            $file = $file->getKey();
        }

        if ($file && $this->filesystem->has($file)) {
            $this->filesystem->delete($file);
        }
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    public function isFileExist($fileName)
    {
        return $this->filesystem->has($fileName);
    }

    /**
     * @param string|File $file
     */
    public function getMimeType($file)
    {
        if ($file instanceof File) {
            $file = $file->getKey();
        }

        if ($file && $this->filesystem->has($file)) {
            try {
                return $this->filesystem->mimeType($file);
            } catch (\LogicException $e) {
                // The underlying adapter does support mimetype.
                return null;
            }
        }

        return null;
    }
}
