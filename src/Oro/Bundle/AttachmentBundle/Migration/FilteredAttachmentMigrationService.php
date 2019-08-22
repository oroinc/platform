<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;

/**
 * Migrate filtered attachments to new directory structure
 * @internal
 */
class FilteredAttachmentMigrationService
{
    /**
     * @var FilesystemMap
     */
    private $filesystemMap;

    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @var ImageFilterLoader
     */
    private $filterLoader;

    /**
     * @var string
     */
    private $fsName;

    /**
     * @param FilesystemMap $filesystemMap
     * @param FilterConfiguration $filterConfiguration
     * @param ImageFilterLoader $filterLoader
     * @param string $fsName
     */
    public function __construct(
        FilesystemMap $filesystemMap,
        FilterConfiguration $filterConfiguration,
        ImageFilterLoader $filterLoader,
        string $fsName
    ) {
        $this->filesystemMap = $filesystemMap;
        $this->filterConfiguration = $filterConfiguration;
        $this->fsName = $fsName;
        $this->filterLoader = $filterLoader;
    }

    /**
     * @param string $fromPrefix
     * @param string $toPrefix
     * @return array
     */
    public function migrate(string $fromPrefix, string $toPrefix)
    {
        $filterPathMap = $this->getFilterPathMap();
        $fs = $this->filesystemMap->get($this->fsName);

        if (!$fs->isDirectory($fromPrefix)) {
            return [];
        }
        $pathRegEx = '/' . str_replace('/', '\/', $fromPrefix) . '\/(\d+)\/([^\/]+)\/([^\/]+)/';

        $processedFiles = [];
        foreach ($fs->listKeys($fromPrefix)['keys'] as $key) {
            $matches = [];
            if (preg_match($pathRegEx, $key, $matches) !== 1) {
                continue;
            }
            $fileId = $matches[1];
            $filterName = $matches[2];
            $fileName = $matches[3];
            if (empty($filterPathMap[$filterName])) {
                continue;
            }

            $newFilePath = $toPrefix . '/' . $filterPathMap[$filterName] . '/' . $fileId . '/' . $fileName;
            if (!$fs->has($newFilePath)) {
                $fs->rename($key, $newFilePath);
            }
            $processedFiles[$fileId] = true;
        }

        return array_keys($processedFiles);
    }

    /**
     * @param string $prefix
     * @param array $subfolders
     */
    public function clear(string $prefix, array $subfolders)
    {
        $fs = $this->filesystemMap->get($this->fsName);
        $adapter = $fs->getAdapter();
        foreach ($subfolders as $subfolder) {
            $adapter->delete($prefix . '/' . $subfolder);
        }
    }

    /**
     * @return array
     */
    private function getFilterPathMap(): array
    {
        $filterMap = [];
        $this->filterLoader->forceLoad();
        foreach ($this->filterConfiguration->all() as $filterName => $config) {
            $filterMap[$filterName] = $filterName . '/' . md5(json_encode($config));
        }

        return $filterMap;
    }
}
