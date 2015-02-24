<?php

namespace Oro\Component\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Loader that returns folder content as a list of found files, works recursively as deep
 * as it's configured by $maxNestingLevel param. There are two possible scenarios
 * how it organizes data loaded: plain and nested, this configured by $plainResultStructure param.
 * It should be used when need to trace directory structure/content updates
 * (including adding new file or removing previosly found), but skip file modification.
 *
 * Examples:
 *   Plain mode
 *      Directory structure:
 *          relative path folder/
 *               file1.yml
 *               foo/
 *                  file2.yml
 *                  bar/
 *                      file3.yml
 *      Loaded result:
 *          [
 *              'relative path folder/file1.yml'
 *              'relative path folder/foo/file2.yml'
 *              'relative path folder/foo/bar/file2.yml'
 *          ]
 *  Nested mode
 *      Directory structure:
 *          relative path folder/
 *               file1.yml
 *               foo/
 *                  file2.yml
 *                  bar/
 *                      file3.yml
 *      Loaded result:
 *          [
 *              0     => 'relative path folder/file1.yml',
 *              'foo' => [
 *                   0     => 'relative path folder/foo/file2.yml',
 *                   'bar' => [
 *                      'relative path folder/foo/bar/file2.yml'
 *                   ]
 *              ]
 *          ]
 */
class FolderContentCummulativeLoader implements CumulativeResourceLoader
{
    /** @var string */
    protected $relativeFolderPath;

    /** @var string int */
    protected $maxNestingLevel;

    /** @var bool */
    protected $plainResultStructure;

    /** @var PropertyAccess */
    protected $propertyAccessor;

    /**
     * @param string $relativeFolderPath
     * @param int    $maxNestingLevel      Pass -1 to unlimit, if you want to find files in exact path given pass 1
     * @param bool   $plainResultStructure Indicates whether result should be returned as flat array
     *                                     or should be nested tree depends on file position in directory hierarchy
     */
    public function __construct($relativeFolderPath, $maxNestingLevel = -1, $plainResultStructure = true)
    {
        $this->relativeFolderPath   = $relativeFolderPath;
        $this->maxNestingLevel      = $maxNestingLevel === -1 ? $maxNestingLevel : --$maxNestingLevel;
        $this->plainResultStructure = $plainResultStructure;
        $this->resource             = 'Folder contents: ' . $relativeFolderPath;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->relativeFolderPath, $this->maxNestingLevel, $this->plainResultStructure]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->relativeFolderPath, $this->maxNestingLevel, $this->plainResultStructure) = unserialize($serialized);
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
    public function load($bundleClass, $bundleDir)
    {
        $dir      = $this->getDirectoryAbsolutePath($bundleDir);
        $realPath = realpath($dir);

        if (!is_dir($realPath)) {
            return null;
        }

        $data = [];
        if ($this->plainResultStructure) {
            $data = $this->getDirectoryContentsArray($realPath);
        } else {
            $iterator           = $this->getDirectoryContents($realPath);
            $absolutePathLength = strlen($realPath);

            foreach ($iterator as $file) {
                $pathName     = $file->getPathname();
                $relativePath = substr($pathName, $absolutePathLength + 1);
                $split        = explode(DIRECTORY_SEPARATOR, $relativePath);
                array_pop($split);

                if (!empty($split)) {
                    $path      = sprintf('[%s]', implode('][', $split));
                    $currValue = $this->getPropertyAccessor()->getValue($data, $path) ?: [];
                    $this->getPropertyAccessor()->setValue($data, $path, array_merge($currValue, [$pathName]));
                } else {
                    $data[] = $pathName;
                }
            }
        }

        return new CumulativeResourceInfo($bundleClass, $this->getResource(), $realPath, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, CumulativeResource $resource)
    {
        $dir      = $this->getDirectoryAbsolutePath($bundleDir);
        $realPath = realpath($dir);

        if (is_dir($realPath)) {
            foreach ($this->getDirectoryContentsArray($realPath) as $filename) {
                $resource->addFound($bundleClass, $filename);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, CumulativeResource $resource, $timestamp)
    {
        $registeredFiles = $resource->getFound($bundleClass);
        $registeredFiles = array_flip($registeredFiles);
        $dir             = $this->getDirectoryAbsolutePath($bundleDir);
        $realPath        = realpath($dir);

        $result = true;
        if (is_dir($realPath)) {
            $currentContents = $this->getDirectoryContentsArray($realPath);

            foreach ($currentContents as $filename) {
                if (!$resource->isFound($bundleClass, $filename)) {
                    $result = false;
                    break;
                }

                unset($registeredFiles[$filename]);
            }

            // case when some file was removed
            if (count($registeredFiles) > 0) {
                $result = false;
            }
        } elseif (!empty($registeredFiles)) {
            // case when entire dir was removed
            $result = false;
        }

        return $result;
    }

    /**
     * @param string $dir
     *
     * @return \SplFileInfo[]|\Iterator
     */
    protected function getDirectoryContents($dir)
    {
        $directoryIterator = new \RecursiveDirectoryIterator($dir);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);
        $recursiveIterator->setMaxDepth($this->maxNestingLevel);
        $iterator = new \CallbackFilterIterator(
            $recursiveIterator,
            function (\SplFileInfo $file) {
                return $file->isFile();
            }
        );

        return $iterator;
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    protected function getDirectoryContentsArray($dir)
    {
        $result = [];
        foreach ($this->getDirectoryContents($dir) as $file) {
            $result[] = $file->getPathname();
        }

        return $result;
    }

    /**
     * @param string $bundleDir
     *
     * @return string
     */
    protected function getDirectoryAbsolutePath($bundleDir)
    {
        return rtrim($bundleDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->relativeFolderPath;
    }

    /**
     * Get PropertyAccessor
     *
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
