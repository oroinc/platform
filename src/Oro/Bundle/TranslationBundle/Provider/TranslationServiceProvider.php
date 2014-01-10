<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Symfony\Component\Finder\Finder;

class TranslationServiceProvider
{
    /** @var AbstractAPIAdapter */
    protected $adapter;

    /** @var JsTranslationDumper */
    protected $jsTranslationDumper;

    /** @var NullLogger */
    protected $logger;

    /**
     * @param AbstractAPIAdapter  $adapter
     * @param JsTranslationDumper $jsTranslationDumper
     */
    public function __construct(AbstractAPIAdapter $adapter, JsTranslationDumper $jsTranslationDumper)
    {
        $this->adapter = $adapter;
        $this->jsTranslationDumper = $jsTranslationDumper;

        $this->setLogger(new NullLogger());
    }

    /**
     * Loop through the generated files in $dir and merge them with downloaded in $targetDir
     * merge generated files over downloaded and upload result back to remote
     *
     * @param string $dir
     *
     * @return mixed
     */
    public function update($dir)
    {
        $pathToSave = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oro-trans' . DIRECTORY_SEPARATOR . 'update.zip';
        $targetDir  = dirname($pathToSave);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $targetDir = $targetDir . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR;

        $isDownloaded = $this->download($pathToSave, 'en', false);
        if (!$isDownloaded) {
            return false;
        }

        $finder = Finder::create()->files()->name('*.yml')->in($dir);
        foreach ($finder->files() as $fileInfo) {
            $localContents = file($fileInfo);
            $remoteFile    = $targetDir . $fileInfo->getRelativePathname();

            if (file_exists($remoteFile)) {
                $remoteContents = file($remoteFile);
                array_shift($remoteContents); // remove dashes from the beginning of file
            } else {
                $remoteContents = [];
            }

            $content = array_unique(array_merge($remoteContents, $localContents));
            $content = implode('', $content);

            $remoteDir = dirname($remoteFile);
            if (!is_dir($remoteDir)) {
                mkdir($remoteDir, 0777, true);
            }
            file_put_contents($remoteFile, $content);
        }

        $result =  $this->upload($targetDir, 'update');
        $this->cleanup($targetDir);

        return $result;
    }

    /**
     * Upload translations
     *
     * @param string $dir
     * @param string $mode
     *
     * @return mixed
     */
    public function upload($dir, $mode = 'add')
    {
        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        /** $file \SplFileInfo */
        $files = array();
        foreach ($finder->files() as $file) {
            // crowdin understand only "/" as directory separator :)
            $apiPath = str_replace(array($dir, DIRECTORY_SEPARATOR), array('', '/'), (string)$file);
            $files[ $apiPath ] = (string)$file;
        }

        return $this->adapter->upload($files, $mode);
    }

    /**
     * @param string      $pathToSave path to save translations
     * @param null|string $locale
     * @param bool        $toApply whether apply download packs or not
     *
     * @return bool
     */
    public function download($pathToSave, $locale = null, $toApply = true)
    {
        $targetDir = dirname($pathToSave);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        } else {
            $this->cleanup($targetDir);
        }

        $isDownloaded = $this->adapter->download($pathToSave, $locale);
        $isExtracted  = $this->unzip(
            $pathToSave,
            is_null($locale) ? $targetDir : rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale
        );

        // TODO: consider move this in crowdin adapter or remove
        if ($locale == 'en') {
            // check and fix exported file names, replace $locale_XX locale in file names to $locale
            $this->renameFiles('.en_US.', '.en.', $targetDir);
        }

        if ($isExtracted) {
            unlink($pathToSave);
        }

        if ($toApply) {
            $this->apply('./app/Resources/', $targetDir);
        }

        return $isExtracted && $isDownloaded;
    }

    /**
     * @param string $find      find string in file name
     * @param string $replace   replacement
     * @param string $dir       where to search
     */
    protected function renameFiles($find, $replace, $dir)
    {
        $finder = Finder::create()->files()->name('*.yml')->in($dir);

        /** $file \SplFileInfo */
        foreach ($finder->files() as $file) {
            rename($file, str_replace($find, $replace, $file));
        }
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

        // try to check possible error messages in file
        if ($res === \ZipArchive::ER_NOZIP) {
            $result = $this->adapter->parseResponse(file_get_contents($file));
            if ($result->getName() == 'error') {
                throw new \RuntimeException($result->message, (int)$result->code);
            }
        }

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
     * Returns applied locale codes
     *
     * @param string $targetDir
     * @param string $sourceDir
     *
     * @return array
     */
    protected function apply($targetDir, $sourceDir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $appliedLocales = [];
        foreach ($iterator as $fileInfo) {
            if ($iterator->getDepth() < 1) {
                continue;
            }

            $target = $targetDir . preg_replace(
                '#(' . $sourceDir . '[/|\\\]+[^/\\\]+[/|\\\]+)#',
                '',
                $fileInfo->getPathname()
            );

            $locale = str_replace(
                '-',
                '_',
                preg_replace(
                    '#' . $sourceDir . '[/|\\\]+([^/]+)[/|\\\]+.*#',
                    '$1',
                    $fileInfo->getPathname()
                )
            );
            $appliedLocales[$locale] = $locale;

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

        if ($appliedLocales) {
            $this->cleanup($sourceDir);
            $this->jsTranslationDumper->dumpTranslations($appliedLocales);
        }

        return $appliedLocales;
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

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->adapter->setLogger($this->logger);
        $this->jsTranslationDumper->setLogger($this->logger);

        return $this;
    }
}
