<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @deprecated since 2.1. Please use FileManager class for working with files instead as it supports gaufrette.
 */
class FileSystemOperator
{
    /**
     * @var string
     */
    protected $cacheDirectory;

    /**
     * @var string
     */
    protected $temporaryDirectoryName;

    /**
     * @var string
     */
    protected $temporaryDirectory;

    /**
     * @param string $cacheDirectory
     * @param string $temporaryDirectoryName
     */
    public function __construct($cacheDirectory, $temporaryDirectoryName)
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->temporaryDirectoryName = $temporaryDirectoryName;
    }


    /**
     * @param bool $readOnly
     * @return string
     * @throws \LogicException
     */
    public function getTemporaryDirectory($readOnly = false)
    {
        if (!$this->temporaryDirectory) {
            $cacheDirectory = rtrim($this->cacheDirectory, DIRECTORY_SEPARATOR);
            $temporaryDirectory = $cacheDirectory . DIRECTORY_SEPARATOR . $this->temporaryDirectoryName;
            if (!file_exists($temporaryDirectory) && !is_dir($temporaryDirectory)) {
                $fs = new Filesystem();
                $fs->mkdir($temporaryDirectory, 0755);
                $fs->chmod($temporaryDirectory, 0755);
            }

            if (!is_readable($temporaryDirectory)) {
                throw new \LogicException('Import/export directory is not readable');
            }
            if (!$readOnly && !is_writable($temporaryDirectory)) {
                throw new \LogicException('Import/export directory is not writeable');
            }

            $this->temporaryDirectory = $temporaryDirectory;
        }

        return $this->temporaryDirectory;
    }

    /**
     * @param $fileName
     * @param bool $readOnly
     * @return \SplFileObject
     * @throws \LogicException
     */
    public function getTemporaryFile($fileName, $readOnly = false)
    {
        $temporaryDirectory = $this->getTemporaryDirectory($readOnly);
        $fullFileName = $temporaryDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($fullFileName) || !is_file($fullFileName) || !is_readable($fullFileName)) {
            throw new \LogicException(sprintf('Can\'t read file %s', $fileName));
        }

        return new \SplFileObject($fullFileName);
    }

    /**
     * @param string $prefix
     * @param string $extension
     * @return string
     */
    public function generateTemporaryFileName($prefix, $extension = 'tmp')
    {
        $temporaryDirectory = $this->getTemporaryDirectory();

        $filePrefix = sprintf('%s_%s', $prefix, date('Y_m_d_H_i_s'));
        do {
            $fileName = sprintf(
                '%s%s%s.%s',
                $temporaryDirectory,
                DIRECTORY_SEPARATOR,
                preg_replace('~\W~', '_', uniqid($filePrefix . '_')),
                $extension
            );
        } while (file_exists($fileName));

        return $fileName;
    }
}
