<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Stream;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\HttpFoundation\File\File as SymfonyComponentFile;

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
     * @param SymfonyComponentFile $file
     *
     * @return string
     */
    public function saveImportingFile(SymfonyComponentFile $file)
    {
        $tmpFileName = self::generateUniqueFileName($file->getClientOriginalExtension());
        $this->saveFileToStorage($file, $tmpFileName);

        return $tmpFileName;
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
     * @param string $fileName
     * @return string
     */
    public function writeToTmpLocalStorage($fileName)
    {
        $content = $this->filesystem->read($fileName);
        $pathFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
        @file_put_contents($pathFile, $content);

        return $pathFile;
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
     * @param string $fileName
     */
    public function deleteFile($fileName)
    {
        if ($fileName && $this->filesystem->has($fileName)) {
            $this->filesystem->delete($fileName);
        }
    }
}
