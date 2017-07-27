<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

class MediaCacheManager extends GaufretteFileManager
{
    /**
     * @var string
     */
    private $mediaCachePrefix;

    /**
     * @param string $filesystemName
     * @param string  $mediaCachePrefix
     */
    public function __construct($filesystemName, $mediaCachePrefix)
    {
        parent::__construct($filesystemName);
        $this->mediaCachePrefix = $mediaCachePrefix;
    }

    /**
     * @param string $content
     * @param string $path
     * @return void
     */
    public function store($content, $path)
    {
        $this->writeToStorage($content, $this->preparePath($path));
    }

    /**
     * @param string $path
     * @return bool
     */
    public function exists($path)
    {
        return $this->filesystem->has($this->preparePath($path));
    }

    /**
     * Remove media cache prefix (/media/cache) from path
     * @param string $path
     * @return string
     */
    protected function preparePath($path)
    {
        return preg_replace(sprintf('|^/?%s/|i', $this->mediaCachePrefix), '', $path);
    }
}
