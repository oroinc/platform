<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Symfony\Component\Finder\Finder;

use Oro\Bundle\TranslationBundle\Provider\AbstractAPIAdapter;

class TranslationServiceProvider
{
    /**
     * @var AbstractAPIAdapter
     */
    protected $adapter;

    /**
     * @param AbstractAPIAdapter $adapter
     */
    public function __construct(AbstractAPIAdapter $adapter = null)
    {
        $this->adapter = $adapter;
    }

    /**
     * Upload translations
     *
     * @param string $dir
     * @param string $mode add or update
     * @param callable $progressCallback
     *
     * @return mixed
     */
    public function upload($dir, $mode = 'add', \Closure $progressCallback = null)
    {
        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        /** $file \SplFileInfo */
        $files = array();
        foreach ($finder->files() as $file) {
            // crowdin understand only "/" as directory separator :)
            $apiPath = str_replace(array($dir, DIRECTORY_SEPARATOR), array('', '/'), (string)$file);
            $files[ $apiPath ] = (string)$file;
        }

        if (!is_null($progressCallback)) {
            $this->adapter->setProgressCallback($progressCallback);
        }

        return $this->adapter->upload($files, $mode);
    }

    /**
     * @param string   $pathToSave path to save translations
     * @param callable $progressCallback
     *
     * @return bool
     */
    public function download($pathToSave, \Closure $progressCallback = null)
    {
        if (!is_null($progressCallback)) {
            $this->adapter->setProgressCallback($progressCallback);
        }

        $targetDir = dirname($pathToSave);
        $this->cleanup($targetDir);

        $isDownloaded = $this->adapter->download($pathToSave);
        $isExtracted  = $this->unzip($pathToSave, $targetDir);

        if ($isExtracted) {
            unlink($pathToSave);
            $this->apply('./app/Resources/', $targetDir);
            $this->cleanup($targetDir);
        }

        return $isExtracted && $isDownloaded;
    }

    /**
     * We need to keep existing translation strings at remote end,
     * so first we have to download pack and merge with local
     *
     * @param string   $dir
     * @param callable $progressCallback
     */
    public function update($dir, \Closure $progressCallback = null)
    {

    }

    /**
     * @param string $file
     * @param string $path
     *
     * @return bool
     * @throws \RuntimeException
     */
    protected function unzip($file, $path)
    {
        $zip = new \ZipArchive();
        $res = $zip->open($file);

        $zipErrors = [
            \ZipArchive::ER_EXISTS => 'File already exists.',
            \ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
            \ZipArchive::ER_INVAL  => 'Invalid argument.',
            \ZipArchive::ER_MEMORY => 'Malloc failure.',
            \ZipArchive::ER_NOENT  => 'No such file.',
            \ZipArchive::ER_NOZIP  => 'Not a zip archive.',
            \ZipArchive::ER_OPEN   => 'Can\'t open file.',
            \ZipArchive::ER_READ   => 'Read error.',
            \ZipArchive::ER_SEEK   => 'Seek error.',
        ];

        if ($res !== true) {
            throw new \RuntimeException($zipErrors[$res], $res);
        }

        $isExtracted = $zip->extractTo($path);
        if (!$isExtracted) {
            throw new \RuntimeException(sprintf('Pack %s can\'t be extracted', $file));
        }

        $isClosed    = $zip->close();
        if (!$isClosed) {
            throw new \RuntimeException(sprintf('Pack %s can\'t be closed', $file));
        }

        return true;
    }

    /**
     * Cleanup directory
     *
     * @param string $targetDir
     */
    protected function cleanup($targetDir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }
    }

    /**
     * Apply downloaded and extracted language packs to Symfony, in app/Resources dir
     *
     * @param string $targetDir
     * @param string $sourceDir
     */
    protected function apply($targetDir, $sourceDir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($iterator->getDepth() < 1) {
                continue;
            }

            $target = $targetDir . preg_replace(
                '#(' . $sourceDir . '[/|\\\]+[^/\\\]+[/|\\\]+)#',
                '',
                $fileInfo->getPathname()
            );

            if ($fileInfo->isDir() && !file_exists($target)) {
                mkdir($target);
            }

            if ($fileInfo->isFile()) {
                rename($fileInfo->getPathname(), $target);
                file_put_contents(
                    $target,
                    trim(
                        str_replace('---', '', file_get_contents($target))
                    )
                );
            }
        }
    }

    /**
     * @param AbstractAPIAdapter $adapter
     *
     * @return $this
     */
    public function setAdapter(AbstractAPIAdapter $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }
}
