<?php

namespace Oro\Bundle\DataGridBundle\Provider\Cache;

use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\ResourceCheckerConfigCache;

/**
 * Provides helper functions for grid caching
 */
class GridCacheUtils
{
    protected int $cacheDirLength;

    public function __construct(private readonly string $cacheDir)
    {
        $this->cacheDirLength = \strlen($this->cacheDir);
    }

    public function getGridConfigCache(string $gridName, string $folderName = null): ConfigCacheInterface
    {
        return new ResourceCheckerConfigCache($this->getGridFile($gridName, $folderName));
    }

    private function getGridFile(string $gridName, ?string $folderName): string
    {
        // This ensures that the filename does not contain invalid chars.
        $fileName = \preg_replace('#[^a-z0-9-_]#i', '-', $gridName);

        // This ensures that the filename is not too long.
        // Most filesystems have a limit of 255 chars for each path component.
        // On Windows the the whole path is limited to 260 chars (including terminating null char).
        $fileNameLength = \strlen($fileName) + 4; // 4 === strlen('.php')
        if ($folderName) {
            $folderName = '/' . $folderName;
            $folderLength = \strlen($folderName);
        } else {
            $folderName = '';
            $folderLength = 0;
        }
        if ($fileNameLength > 255 || $this->cacheDirLength + $folderLength + $fileNameLength > 259) {
            $fileName = \hash('sha256', $gridName);
        }

        return \sprintf('%s/%s.php', $this->cacheDir . $folderName, $fileName);
    }
}
