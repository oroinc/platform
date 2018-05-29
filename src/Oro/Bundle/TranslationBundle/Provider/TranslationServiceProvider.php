<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReader;

/**
 * Merges generated files over downloaded and upload result back to remoter
 */
class TranslationServiceProvider
{
    const FILE_NAME_SUFFIX      = '.zip';
    const DEFAULT_SOURCE_LOCALE = 'en';

    /** @var AbstractAPIAdapter */
    protected $adapter;

    /** @var JsTranslationDumper */
    protected $jsTranslationDumper;

    /** @var NullLogger */
    protected $logger;

    /** @var TranslationReader */
    protected $translationReader;

    /** @var DatabasePersister  */
    protected $databasePersister;

    /** @var string */
    protected $cacheDir;

    /**
     * @param AbstractAPIAdapter  $adapter
     * @param JsTranslationDumper $jsTranslationDumper
     * @param TranslationReader   $translationReader
     * @param DatabasePersister   $databasePersister
     * @param string              $cacheDir
     */
    public function __construct(
        AbstractAPIAdapter $adapter,
        JsTranslationDumper $jsTranslationDumper,
        TranslationReader $translationReader,
        DatabasePersister $databasePersister,
        $cacheDir
    ) {
        $this->adapter             = $adapter;
        $this->jsTranslationDumper = $jsTranslationDumper;
        $this->translationReader   = $translationReader;
        $this->databasePersister   = $databasePersister;
        $this->cacheDir            = $cacheDir;

        $this->setLogger(new NullLogger());
    }

    /**
     * Loop through the generated files in $dirs and merge them with downloaded in $targetDir
     * merge generated files over downloaded and upload result back to remote
     *
     * @param array|string[] $dirs
     * @return bool
     */
    public function update($dirs)
    {
        $dirs       = $this->processDirs($dirs);
        $targetDir  = $this->getTmpDir('oro-trans');
        $pathToSave = $targetDir . DIRECTORY_SEPARATOR . 'update';
        $targetDir  = $targetDir . DIRECTORY_SEPARATOR . self::DEFAULT_SOURCE_LOCALE . DIRECTORY_SEPARATOR;

        $isDownloaded = $this->download($pathToSave, [], self::DEFAULT_SOURCE_LOCALE);
        if (!$isDownloaded) {
            return false;
        }

        foreach ($dirs as $dir) {
            $finder = Finder::create()->files()->name('*.yml')->in($dir);

            /** @var SplFileInfo $fileInfo */
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
        }


        $this->upload($targetDir, 'update');
        $this->cleanup($targetDir);

        return true;
    }

    /**
     * Upload translations
     *
     * @param string|array $dirs
     * @param string $mode
     *
     * @return mixed
     */
    public function upload($dirs, $mode = 'add')
    {
        $dirs = $this->processDirs($dirs);

        $finder = Finder::create()->files()->name('*.yml')->in($dirs);

        /** $file \SplFileInfo */
        $files = [];
        foreach ($finder->files() as $file) {
            $apiPath = (string)$file;
            foreach ($dirs as $dir) {
                if (strpos($apiPath, $dir) !== false) {
                    $apiPath = str_replace($dir, '', $apiPath);
                    break;
                }
            }

            // crowdin understand only "/" as directory separator :)
            $apiPath = str_replace(DIRECTORY_SEPARATOR, '/', $apiPath);

            $files[$apiPath] = (string)$file;
        }

        return $this->adapter->upload($files, $mode);
    }

    /**
     * @param string      $pathToSave path to save translations
     * @param array       $projects   project names
     * @param null|string $locale
     *
     * @throws \RuntimeException
     * @return bool
     */
    public function download($pathToSave, array $projects, $locale = null)
    {
        $pathToSave .= self::FILE_NAME_SUFFIX;
        $targetDir  = dirname($pathToSave);
        $this->cleanup($targetDir);

        return $this->adapter->download($pathToSave, $projects, $locale);
    }

    /**
     * @param string      $pathToSave path to save translations
     * @param null|string $locale
     *
     * @throws \RuntimeException
     * @return bool
     */
    public function loadTranslatesFromFile($pathToSave, $locale = null)
    {
        $pathToSave .= self::FILE_NAME_SUFFIX;
        $targetDir = dirname($pathToSave);

        $isExtracted  = $this->unzip(
            $pathToSave,
            is_null($locale) ? $targetDir : rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $locale
        );

        if ($locale === 'en') {
            // check and fix exported file names, replace $locale_XX locale in file names to $locale
            $this->renameFiles('.en_US.', '.en.', $targetDir);
        }

        if ($isExtracted) {
            unlink($pathToSave);

            $this->apply($locale, $targetDir);

            $this->cleanup($targetDir);
            $this->jsTranslationDumper->dumpTranslations([$locale]);
        }

        return $isExtracted;
    }

    /**
     * @param string|array $dirs
     * @return array
     */
    protected function processDirs($dirs)
    {
        $dirs = is_array($dirs) ? $dirs : [$dirs];

        return array_map(
            function ($path) {
                return rtrim($path, DIRECTORY_SEPARATOR);
            },
            $dirs
        );
    }

    /**
     * @param string $find    find string in file name
     * @param string $replace replacement
     * @param string $dir     where to search
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

        if ($res !== true) {
            throw new \RuntimeException($zipErrors[$res], $res);
        }

        $isExtracted = $zip->extractTo($path);
        if (!$isExtracted) {
            throw new \RuntimeException(sprintf('Pack %s can\'t be extracted', $file));
        }

        $isClosed = $zip->close();
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
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
            return;
        }

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
     * @param string $locale
     * @param string $sourceDir
     */
    protected function apply($locale, $sourceDir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $catalog = new MessageCatalogue($locale);
        /** @var \SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if ($iterator->getDepth() < 1 || $fileInfo->isDir()) {
                continue;
            }

            // fix bad formatted yaml that may come from third-party service
            YamlFixer::fixStrings($fileInfo->getPathname());
        }

        $this->translationReader->read($sourceDir, $catalog);
        $this->databasePersister->persist($locale, $catalog->all());
    }

    /**
     * @param APIAdapterInterface $adapter
     */
    public function setAdapter(APIAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AbstractAPIAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets a logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->adapter->setLogger($this->logger);
        $this->jsTranslationDumper->setLogger($this->logger);
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public function getTmpDir($prefix)
    {
        $pathParts = [
            rtrim($this->cacheDir, DIRECTORY_SEPARATOR),
            'translations',
            ltrim(uniqid($prefix), DIRECTORY_SEPARATOR)
        ];
        $path = implode(DIRECTORY_SEPARATOR, $pathParts);

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}
