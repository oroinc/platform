<?php

namespace Oro\Bundle\SecurityBundle\Annotation\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeResourceLoader;
use Symfony\Component\Finder\Finder;

class AclAnnotationCumulativeResourceLoader implements CumulativeResourceLoader
{
    /**
     * @var string[]
     */
    private $subDirs;

    /**
     * Constructor
     *
     * @param string[] $subDirs  A list of sub directories (related to a bundle directory)
     *                           where classes with ACL annotations may be located
     */
    public function __construct(array $subDirs = [])
    {
        $this->subDirs = $subDirs;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return 'oro_acl_annotations';
    }

    /**
     * {@inheritdoc}
     */
    public function load($bundleClass, $bundleDir, $bundleAppDir = '')
    {
        $finder = $this->getFileFinder($bundleDir);
        if (!$finder) {
            return null;
        }

        $files = [];
        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }
        if (empty($files)) {
            return null;
        }

        return new CumulativeResourceInfo(
            $bundleClass,
            $this->getResource(),
            null,
            $files
        );
    }

    /**
     * {@inheritdoc}
     */
    public function registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource)
    {
        $finder = $this->getFileFinder($bundleDir);
        if ($finder) {
            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $resource->addFound($bundleClass, $file->getRealPath());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, CumulativeResource $resource, $timestamp)
    {
        // check exist and removed resources
        $found = $resource->getFound($bundleClass);
        foreach ($found as $path) {
            if (!is_file($path) || filemtime($path) >= $timestamp) {
                return false;
            }
        }
        // check new resources
        $finder = $this->getFileFinder($bundleDir);
        if ($finder) {
            /** @var \SplFileInfo $file */
            foreach ($finder as $file) {
                $path = $file->getRealPath();
                if (!$resource->isFound($bundleClass, $path)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->subDirs);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->subDirs = unserialize($serialized);
    }

    /**
     * @param $bundleDir
     * @return array
     */
    protected function getDirectoriesToCheck($bundleDir)
    {
        $result = [];

        if (!empty($this->subDirs)) {
            foreach ($this->subDirs as $subDir) {
                $dir = $bundleDir . DIRECTORY_SEPARATOR . $subDir;
                if (is_dir($dir)) {
                    $result[] = $dir;
                }
            }
        } else {
            $result[] = $bundleDir;
        }

        return $result;
    }

    /**
     * @param string $bundleDir
     * @return Finder|null
     */
    protected function getFileFinder($bundleDir)
    {
        $dirs = $this->getDirectoriesToCheck($bundleDir);
        if (empty($dirs)) {
            return null;
        }

        $finder = new Finder();
        $finder
            ->files()
            ->name('*.php')
            ->in($dirs)
            ->ignoreVCS(true);

        return $finder;
    }
}
