<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;

/**
 * Loader that returns folder content as a list of found files, works recursively as deep
 * as it's configured by $maxNestingLevel param. There are two possible scenarios
 * how it organizes data loaded: plain and nested, this configured by $plainResultStructure param.
 * It should be used when need to trace directory structure/content updates
 * (including adding new file or removing previously found), but skip file modification.
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
class FolderContentCumulativeLoader implements CumulativeResourceLoader
{
    /** @var string */
    protected $relativeFolderPath;

    /** @var string int */
    protected $maxNestingLevel;

    /** @var bool */
    protected $plainResultStructure;

    /** @var string[] */
    protected $fileExtensions;

    /** @var PropertyAccess */
    protected $propertyAccessor;

    /** @var string */
    protected $resource;

    /**
     * @param string   $relativeFolderPath
     * @param int      $maxNestingLevel      Pass -1 to unlimit, if you want to find files in exact path given pass 1
     * @param bool     $plainResultStructure Indicates whether result should be returned as flat array
     *                                       or should be nested tree depends on file position in directory hierarchy
     * @param string[] $fileExtensions       The extensions of files to be scanned
     */
    public function __construct(
        $relativeFolderPath,
        $maxNestingLevel = -1,
        $plainResultStructure = true,
        array $fileExtensions = []
    ) {
        $this->relativeFolderPath   = $relativeFolderPath;
        $this->maxNestingLevel      = $maxNestingLevel === -1 ? $maxNestingLevel : --$maxNestingLevel;
        $this->plainResultStructure = $plainResultStructure;
        $this->fileExtensions       = $fileExtensions;
        $this->resource             = 'Folder contents: ' . $relativeFolderPath;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->relativeFolderPath,
                $this->maxNestingLevel,
                $this->plainResultStructure,
                $this->fileExtensions
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->relativeFolderPath,
            $this->maxNestingLevel,
            $this->plainResultStructure,
            $this->fileExtensions
            ) = unserialize($serialized);
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
        $dir           = $this->getDirectoryAbsolutePath($bundleDir);
        $bundleAppData = [];

        if (is_dir($bundleAppDir)) {
            $appDir        = $this->getResourcesDirectoryAbsolutePath($bundleAppDir);
            $bundleAppData = $this->getData($appDir);
        }

        $data = $this->mergeArray($bundleAppData, $this->getData($dir), $bundleAppDir, $bundleDir);
        if (empty($data)) {
            return null;
        }

        return new CumulativeResourceInfo($bundleClass, $this->getResource(), realpath($dir), $data);
    }

    /**
     * Recursively merges two arrays into one. If files have the same location,
     * the priority remains for the array $a
     *
     * @param array  $a            Array to merge
     * @param array  $b            Array, which merges with the previous one
     * @param string $bundleAppDir The bundle directory inside the application resources directory
     * @param string $bundleDir    The bundle root directory
     *
     * @return array              Merged array
     */
    protected function mergeArray(array $a, array $b, $bundleAppDir, $bundleDir)
    {
        foreach ($b as $k => $v) {
            if (is_int($k)) {
                foreach ($a as $val) {
                    if ($this->isFilePathEquals($val, $v, $bundleAppDir, $bundleDir)) {
                        continue 2;
                    }
                }
                $a[] = $v;
            } else {
                if (is_array($v) && isset($a[$k]) && is_array($a[$k])) {
                    $a[$k] = $this->mergeArray($a[$k], $v, $bundleAppDir, $bundleDir);
                } else {
                    $a[$k] = $v;
                }
            }
        }

        return $a;
    }

    /**
     * Equals end of files names from $bundleAppDir and $bundleDir
     *
     * @param string $bundleAppPath Path to the resource file from $resourceDir
     * @param string $bundlePath    Path to the resource file from $bundlePath
     * @param string $bundleAppDir  Directory to the priority resource
     * @param string $bundleDir     The bundle root directory
     *
     * @return bool
     */
    protected function isFilePathEquals($bundleAppPath, $bundlePath, $bundleAppDir, $bundleDir)
    {
        $a = str_replace($bundleDir . DIRECTORY_SEPARATOR . 'Resources', '', $bundlePath);
        $b = DIRECTORY_SEPARATOR !== '/'
            ? str_replace(str_replace('/', DIRECTORY_SEPARATOR, $bundleAppDir), '', $bundleAppPath)
            : str_replace($bundleAppDir, '', $bundleAppPath);

        return $a === $b;
    }

    /**
     * Get all data from the directory $dir
     *
     * @param $dir
     *
     * @return array
     */
    protected function getData($dir)
    {
        $realPath = realpath($dir);
        if (!is_dir($realPath)) {
            return [];
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

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource)
    {
        $dir           = $this->getResourcesDirectoryAbsolutePath($bundleAppDir);
        $realPath      = realpath($dir);
        $bundleAppData = [];
        if (is_dir($realPath)) {
            $bundleAppData = $this->getDirectoryContentsArray($realPath);
        }

        $dir        = $this->getDirectoryAbsolutePath($bundleDir);
        $realPath   = realpath($dir);
        $bundleData = [];
        if (is_dir($realPath)) {
            $bundleData = $this->getDirectoryContentsArray($realPath);
        }

        foreach ($this->mergeArray($bundleAppData, $bundleData, $bundleAppDir, $bundleDir) as $filename) {
            $resource->addFound($bundleClass, $filename);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp)
    {
        $registeredFiles = $resource->getFound($bundleClass);
        $registeredFiles = array_flip($registeredFiles);

        // Check and remove data from $bundleAppDir resources directory
        if (is_dir($bundleAppDir)) {
            $dir      = $this->getResourcesDirectoryAbsolutePath($bundleAppDir);
            $realPath = realpath($dir);
            if (is_dir($realPath)) {
                $currentContents = $this->getDirectoryContentsArray($realPath);

                foreach ($currentContents as $filename) {
                    if (!$resource->isFound($bundleClass, $filename)) {
                        return false;
                    }

                    unset($registeredFiles[$filename]);
                }
            }
        }

        // Check and remove data from $bundleDir resources directory
        $dir      = $this->getDirectoryAbsolutePath($bundleDir);
        $realPath = realpath($dir);
        if (is_dir($realPath)) {
            $currentContents = $this->getDirectoryContentsArray($realPath);

            foreach ($currentContents as $filename) {
                if (!$resource->isFound($bundleClass, $filename)) {
                    return false;
                }

                unset($registeredFiles[$filename]);
            }
        }

        // case when entire dir was removed or some file was removed
        if (!empty($registeredFiles)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return \SplFileInfo[]|\Iterator
     */
    protected function getDirectoryContents($dir)
    {
        $recursiveIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        $recursiveIterator->setMaxDepth($this->maxNestingLevel);
        $iterator = new \CallbackFilterIterator(
            $recursiveIterator,
            function (\SplFileInfo $file) {
                return empty($this->fileExtensions)
                    ? true
                    : in_array($file->getExtension(), $this->fileExtensions, true);
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

        $files = $this->getDirectoryContents($dir);
        foreach ($files as $file) {
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
     * @param string $bundleAppDir
     *
     * @return string
     */
    protected function getResourcesDirectoryAbsolutePath($bundleAppDir)
    {
        return
            rtrim($bundleAppDir, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            preg_replace('/Resources\//', '', $this->relativeFolderPath, 1);
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
