<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;

/**
 * FolderingCumulativeFileLoader represents a file resource located in some folder.
 * It allows to make the container dirty when a new folder with this file resource is created or deleted.
 */
class FolderingCumulativeFileLoader implements CumulativeResourceLoader
{
    /**
     * @var string
     */
    protected $folderPlaceholder;

    /**
     * @var string
     */
    protected $folderPattern;

    /**
     * @var CumulativeFileLoader[]
     */
    protected $fileResourceLoaders;

    /**
     * @var array
     *
     * not serializable. it sets in initialize method
     */
    protected $preparedRelativeFilePaths;

    /**
     * @var array
     */
    protected $registeredRelativeFilePaths = [];

    /**
     * @var string
     *
     * not serializable. it sets in initialize method
     */
    protected $resource;

    /**
     * @param string                                      $folderPlaceholder
     * @param string                                      $folderPattern
     * @param CumulativeFileLoader|CumulativeFileLoader[] $fileResourceLoader
     */
    public function __construct($folderPlaceholder, $folderPattern, $fileResourceLoader)
    {
        $this->folderPlaceholder   = $folderPlaceholder;
        $this->folderPattern       = $folderPattern;
        $this->fileResourceLoaders = is_array($fileResourceLoader)
            ? $fileResourceLoader
            : [$fileResourceLoader];

        $this->initialize();
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function load($bundleClass, $bundleDir, $bundleAppDir = '')
    {
        $result = [];

        foreach ($this->fileResourceLoaders as $loader) {
            $split = $this->preparedRelativeFilePaths[(string)$loader->getResource()];
            if (!$split['folder']) {
                $this->addLoadedResource($result, $loader->load($bundleClass, $bundleDir, $bundleAppDir));
            } else {
                $dir = $bundleDir . $split['baseDir'];
                if (is_dir($dir)) {
                    $folderPattern = $this->getFolderPattern($split['folder']);
                    $iterator      = new \DirectoryIterator($dir);
                    /** @var \DirectoryIterator $file */
                    foreach ($iterator as $file) {
                        if ($this->isApplicableFolder($file, $folderPattern)) {
                            $originalRelativeFilePath = $loader->getRelativeFilePath();
                            $currentRelativeFilePath  =
                                $split['baseDir'] . DIRECTORY_SEPARATOR . $file->getFilename() . $split['relPath'];
                            try {
                                $loader->setRelativeFilePath($currentRelativeFilePath);
                                $this->addLoadedResource(
                                    $result,
                                    $loader->load($bundleClass, $bundleDir, $bundleAppDir)
                                );
                            } catch (\Exception $e) {
                                $loader->setRelativeFilePath($originalRelativeFilePath);
                                throw $e;
                            }
                            $loader->setRelativeFilePath($originalRelativeFilePath);
                        }
                    }
                }
            }
        }

        if (empty($result)) {
            $result = null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource)
    {
        foreach ($this->fileResourceLoaders as $loader) {
            $pathKey = (string)$loader->getResource();
            $split = $this->preparedRelativeFilePaths[$pathKey];
            if (!$split['folder']) {
                $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
            } else {
                $dir = $bundleDir . $split['baseDir'];
                if (is_dir($dir)) {
                    $folderPattern = $this->getFolderPattern($split['folder']);
                    $iterator      = new \DirectoryIterator($dir);
                    /** @var \DirectoryIterator $file */
                    foreach ($iterator as $file) {
                        if ($this->isApplicableFolder($file, $folderPattern)) {
                            $originalRelativeFilePath = $loader->getRelativeFilePath();
                            $currentRelativeFilePath  =
                                $split['baseDir'] . DIRECTORY_SEPARATOR . $file->getFilename() . $split['relPath'];
                            try {
                                $loader->setRelativeFilePath($currentRelativeFilePath);
                                $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
                                $this->registeredRelativeFilePaths[$pathKey][$currentRelativeFilePath] = true;
                            } catch (\Exception $e) {
                                $loader->setRelativeFilePath($originalRelativeFilePath);
                                throw $e;
                            }
                            $loader->setRelativeFilePath($originalRelativeFilePath);
                        }
                    }
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp)
    {
        foreach ($this->fileResourceLoaders as $loader) {
            $pathKey = (string)$loader->getResource();
            $split = $this->preparedRelativeFilePaths[$pathKey];
            if (!$split['folder']) {
                if (!$loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $timestamp)) {
                    return false;
                }
            }

            if (array_key_exists($pathKey, $this->registeredRelativeFilePaths)) {
                /** @var array $registeredRelativeFilePaths */
                $registeredRelativeFilePaths = $this->registeredRelativeFilePaths[$pathKey];

                $dir = $bundleDir . $split['baseDir'];
                if ($this->isNewDirectoryCreated($dir, $split, $registeredRelativeFilePaths)) {
                    return false;
                }

                $originalRelativeFilePath = $loader->getRelativeFilePath();
                foreach ($registeredRelativeFilePaths as $relativeFilePath => $value) {
                    try {
                        $loader->setRelativeFilePath($relativeFilePath);
                        if (!$loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $timestamp)) {
                            $loader->setRelativeFilePath($originalRelativeFilePath);

                            return false;
                        }
                    } catch (\Exception $e) {
                        $loader->setRelativeFilePath($originalRelativeFilePath);
                        throw $e;
                    }
                }

                $loader->setRelativeFilePath($originalRelativeFilePath);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->folderPlaceholder,
            $this->folderPattern,
            $this->fileResourceLoaders,
            $this->registeredRelativeFilePaths
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->folderPlaceholder,
            $this->folderPattern,
            $this->fileResourceLoaders,
            $this->registeredRelativeFilePaths
            ) = unserialize($serialized);
        $this->initialize();
    }

    /**
     * Initialises $this->preparedRelativeFilePaths and $this->resource
     */
    protected function initialize()
    {
        $resources                       = [];
        $this->preparedRelativeFilePaths = [];
        foreach ($this->fileResourceLoaders as $loader) {
            $resource = (string)$loader->getResource();
            $split    = $this->splitRelativeFilePath($loader->getRelativeFilePath());

            $this->preparedRelativeFilePaths[$resource] = $split;
            $resources[]                                = $resource;
        }
        $this->resource = 'Foldering:' . implode(';', $resources);
    }

    /**
     * @param string $relativeFilePath
     *
     * @return array
     */
    protected function splitRelativeFilePath($relativeFilePath)
    {
        $pos = strpos($relativeFilePath, $this->folderPlaceholder);
        if (false === $pos) {
            return [
                'baseDir' => null,
                'folder'  => null,
                'relPath' => $relativeFilePath
            ];
        }

        $startDelim = strrpos($relativeFilePath, DIRECTORY_SEPARATOR, -(strlen($relativeFilePath) - $pos));
        $endDelim   = strpos($relativeFilePath, DIRECTORY_SEPARATOR, $pos + strlen($this->folderPlaceholder));

        return [
            'baseDir' => substr($relativeFilePath, 0, $startDelim),
            'folder'  => substr($relativeFilePath, $startDelim, $endDelim - $startDelim),
            'relPath' => substr($relativeFilePath, $endDelim)
        ];
    }

    /**
     * Adds $resource to $result
     *
     * @param array $result
     * @param mixed $resource
     */
    protected function addLoadedResource(array &$result, $resource)
    {
        if (null !== $resource) {
            if (is_array($resource)) {
                foreach ($resource as $res) {
                    $result[] = $res;
                }
            } else {
                $result[] = $resource;
            }
        }
    }

    /**
     * Checks if the current filesystem item can contain a resource
     *
     * @param \DirectoryIterator $file
     * @param string             $folderPattern
     *
     * @return bool
     */
    protected function isApplicableFolder(\DirectoryIterator $file, $folderPattern)
    {
        return
            !$file->isDot()
            && $file->isDir()
            && preg_match($folderPattern, $file->getFilename());
    }

    /**
     * Returns a regular expression pattern which can be used to check if a folder can contain a resource
     *
     * @param string $folder
     *
     * @return string
     */
    protected function getFolderPattern($folder)
    {
        return sprintf(
            '/^%s$/',
            substr(str_replace($this->folderPlaceholder, $this->folderPattern, $folder), 1)
        );
    }

    /**
     * @param string $dir
     * @param array  $split
     * @param array  $registeredRelativeFilePaths
     *
     * @return bool
     */
    protected function isNewDirectoryCreated($dir, $split, $registeredRelativeFilePaths)
    {
        if (is_dir($dir)) {
            $folderPattern = $this->getFolderPattern($split['folder']);
            $iterator = new \DirectoryIterator($dir);
            /** @var \DirectoryIterator $file */
            foreach ($iterator as $file) {
                if ($this->isApplicableFolder($file, $folderPattern)) {
                    $relativeFilePath  =
                        $split['baseDir'] . DIRECTORY_SEPARATOR . $file->getFilename() . $split['relPath'];
                    if (!array_key_exists($relativeFilePath, $registeredRelativeFilePaths)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
