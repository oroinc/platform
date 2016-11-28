<?php

namespace Oro\Component\Layout\Config\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;

class LayoutUpdateCumulativeResourceLoader extends FolderContentCumulativeLoader
{
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
                    if (!$this->isFoundAndFresh($resource, $bundleClass, $filename, $timestamp)) {
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
                if (!$this->isFoundAndFresh($resource, $bundleClass, $filename, $timestamp)) {
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
     * @param CumulativeResource $resource
     * @param string             $bundleClass
     * @param string             $filename
     * @param int                $timestamp
     *
     * @return boolean
     */
    private function isFoundAndFresh(CumulativeResource $resource, $bundleClass, $filename, $timestamp)
    {
        return $resource->isFound($bundleClass, $filename) && is_file($filename) && filemtime($filename) < $timestamp;
    }
}
