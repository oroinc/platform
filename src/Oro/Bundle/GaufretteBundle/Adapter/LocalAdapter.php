<?php

namespace Oro\Bundle\GaufretteBundle\Adapter;

use Gaufrette\Adapter\ListKeysAware;
use Gaufrette\Adapter\Local as BaseAdapter;

/**
 * Adds implementation of ListKeysAware interface to provide a performance optimized way to list
 * files and directories by a prefix.
 */
class LocalAdapter extends BaseAdapter implements ListKeysAware
{
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * {@inheritDoc}
     */
    public function listKeys($prefix = '')
    {
        $directory = $this->getRootDirectoryForListKeys($prefix);
        if (!is_dir($directory)) {
            return ['keys' => [], 'dirs' => []];
        }

        try {
            $foundFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
        } catch (\Exception $e) {
            return ['keys' => [], 'dirs' => []];
        }

        $files = [];
        $dirs = [];
        /** @var \SplFileInfo $file */
        foreach ($foundFiles as $file) {
            $key = $this->computeKey($file->getPathname());
            if (!$prefix || str_starts_with($key, $prefix)) {
                if ($file->isDir()) {
                    $dirs[] = $key;
                } else {
                    $files[] = $key;
                }
            }
        }
        sort($files);
        sort($dirs);

        return ['keys' => $files, 'dirs' => $dirs];
    }

    private function getRootDirectoryForListKeys(string $prefix): string
    {
        $lastSeparatorPos = strrpos($prefix, '/');
        if (false === $lastSeparatorPos) {
            return $this->directory;
        }

        return $this->normalizePath($this->directory . '/' . substr($prefix, 0, $lastSeparatorPos));
    }
}
