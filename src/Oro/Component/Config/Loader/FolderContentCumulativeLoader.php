<?php

namespace Oro\Component\Config\Loader;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * The loader that returns folder content as a list of found files, works recursively as deep
 * as it's configured by $maxNestingLevel param. There are two possible scenarios
 * how it organizes data loaded: plain and nested, this configured by $plainResultStructure param.
 * It should be used when need to trace directory structure/content updates
 * (including adding a new file, removing or modifying a previously found file).
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
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FolderContentCumulativeLoader implements CumulativeResourceLoader
{
    /** @var string */
    protected $relativeFolderPath;

    /** @var string int */
    protected $maxNestingLevel;

    /** @var bool */
    protected $plainResultStructure;

    /** @var FileMatcherInterface */
    protected $fileMatcher;

    /** @var PropertyAccess|null */
    protected $propertyAccessor;

    /**
     * @param string                    $relativeFolderPath   The relative path to a directory to be scanned
     * @param int                       $maxNestingLevel      Pass -1 to unlimit,
     *                                                        if you want to find files in exact path given pass 1
     * @param bool                      $plainResultStructure Indicates whether result should be returned
     *                                                        as flat array or should be nested tree depends on
     *                                                        file position in directory hierarchy
     * @param FileMatcherInterface|null $fileMatcher          The matcher that are used to filter files to be scanned
     */
    public function __construct(
        $relativeFolderPath,
        $maxNestingLevel = -1,
        $plainResultStructure = true,
        FileMatcherInterface $fileMatcher = null
    ) {
        $this->relativeFolderPath = $relativeFolderPath;
        $this->maxNestingLevel = -1 === $maxNestingLevel
            ? $maxNestingLevel
            : --$maxNestingLevel;
        $this->plainResultStructure = $plainResultStructure;
        $this->fileMatcher = $fileMatcher;
    }

    public function __serialize(): array
    {
        return [
            $this->relativeFolderPath,
            $this->maxNestingLevel,
            $this->plainResultStructure,
            $this->fileMatcher
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->relativeFolderPath,
            $this->maxNestingLevel,
            $this->plainResultStructure,
            $this->fileMatcher
        ] = $serialized;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return 'Folder content: ' . $this->relativeFolderPath;
    }

    /**
     * {@inheritdoc}
     */
    public function load($bundleClass, $bundleDir, $bundleAppDir = '')
    {
        $bundleAppData = [];
        if (CumulativeResourceManager::getInstance()->isDir($bundleAppDir)) {
            $bundleAppData = $this->getData($this->getResourcesDirectoryAbsolutePath($bundleAppDir));
        }

        $dir = $this->getDirectoryAbsolutePath($bundleDir);
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
        $result = [];
        foreach ($b as $k => $v) {
            if (is_int($k)) {
                if ($this->isOverriddenByAppFile($v, $a, $bundleAppDir, $bundleDir)) {
                    continue;
                }
                $result[] = $v;
            } elseif (is_array($v) && isset($a[$k]) && is_array($a[$k])) {
                $result[$k] = $this->mergeArray($a[$k], $v, $bundleAppDir, $bundleDir);
            } else {
                $result[$k] = $v;
            }
        }

        return array_merge($a, $result);
    }

    /**
     * Checks if the given file from a bundle is overridden be a file from an application
     *
     * @param string   $bundlePath
     * @param string[] $bundleAppPaths
     * @param string   $bundleAppDir
     * @param string   $bundleDir
     *
     * @return bool
     */
    protected function isOverriddenByAppFile($bundlePath, $bundleAppPaths, $bundleAppDir, $bundleDir)
    {
        foreach ($bundleAppPaths as $bundleAppPath) {
            if ($this->isFilePathEquals($bundleAppPath, $bundlePath, $bundleAppDir, $bundleDir)) {
                return true;
            }
        }

        return false;
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
        if (DIRECTORY_SEPARATOR !== '/') {
            $bundleAppDir = str_replace('/', DIRECTORY_SEPARATOR, $bundleAppDir);
        }
        $b = str_replace($bundleAppDir, '', $bundleAppPath);

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
            $iterator = $this->getDirectoryContents($realPath);
            $absolutePathLength = strlen($realPath);

            foreach ($iterator as $file) {
                $pathName = $file->getPathname();
                $relativePath = substr($pathName, $absolutePathLength + 1);
                $split = explode(DIRECTORY_SEPARATOR, $relativePath);
                array_pop($split);

                if (!empty($split)) {
                    $path = sprintf('[%s]', implode('][', $split));
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
        $bundleAppData = [];
        $realPath = realpath($this->getResourcesDirectoryAbsolutePath($bundleAppDir));
        if (is_dir($realPath)) {
            $bundleAppData = $this->getDirectoryContentsArray($realPath);
        }

        $bundleData = [];
        $realPath = realpath($this->getDirectoryAbsolutePath($bundleDir));
        if (is_dir($realPath)) {
            $bundleData = $this->getDirectoryContentsArray($realPath);
        }

        $fileNames = $this->mergeArray($bundleAppData, $bundleData, $bundleAppDir, $bundleDir);
        foreach ($fileNames as $filename) {
            $resource->addFound($bundleClass, $filename);
        }
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp)
    {
        $registeredFiles = array_fill_keys($resource->getFound($bundleClass), false);

        $registeredAppFiles = [];
        if (CumulativeResourceManager::getInstance()->isDir($bundleAppDir)) {
            $realPath = realpath($this->getResourcesDirectoryAbsolutePath($bundleAppDir));
            if (is_dir($realPath)) {
                $files = $this->getDirectoryContentsArray($realPath);
                foreach ($files as $filename) {
                    if (!$this->isResourceFileFresh($resource, $bundleClass, $filename, $timestamp)) {
                        return false;
                    }

                    $registeredFiles[$filename] = true;
                    $registeredAppFiles[] = $filename;
                }
            }
        }

        $realPath = realpath($this->getDirectoryAbsolutePath($bundleDir));
        if (is_dir($realPath)) {
            $files = $this->getDirectoryContentsArray($realPath);
            foreach ($files as $filename) {
                if (!$this->isResourceFileFresh($resource, $bundleClass, $filename, $timestamp)
                    && !$this->isOverriddenByAppFile($filename, $registeredAppFiles, $bundleAppDir, $bundleDir)
                ) {
                    return false;
                }

                $registeredFiles[$filename] = true;
            }
        }

        foreach ($registeredFiles as $isFileFresh) {
            if (!$isFileFresh) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param CumulativeResource $resource
     * @param string             $bundleClass
     * @param string             $filename
     * @param int                $timestamp
     *
     * @return boolean
     */
    protected function isResourceFileFresh(CumulativeResource $resource, $bundleClass, $filename, $timestamp)
    {
        if (!$resource->isFound($bundleClass, $filename)) {
            return false;
        }

        $filemtime = @filemtime($filename);

        return false !== $filemtime && $filemtime < $timestamp;
    }

    protected function getDirectoryContents(string $dir): iterable
    {
        $finder = Finder::create()->in($dir);

        if ($this->maxNestingLevel >= 0) {
            $finder->depth('<=' . $this->maxNestingLevel);
        }

        if ($this->fileMatcher) {
            $finder->filter(fn (\SplFileInfo $file) => $this->fileMatcher->isMatched($file));
        }

        // Adds sorting by depth to ensure that result is not affected by OS.
        $finder->sort(static function (\SplFileInfo $file1, \SplFileInfo $file2) {
            $depth1 = substr_count($file1->getPath(), DIRECTORY_SEPARATOR);
            $depth2 = substr_count($file2->getPath(), DIRECTORY_SEPARATOR);
            
            return $depth1 <=> $depth2;
        });

        return $finder->files();
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
