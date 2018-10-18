<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

use Symfony\Component\Filesystem\Filesystem;

class CopyFixturesToTemp
{
    /** @var string */
    private $target;

    /** @var string */
    private $source;

    /**
     * @param $target
     * @param $source
     */
    public function __construct($target, $source)
    {
        $this->target = $target;
        $this->source = $source;
    }

    /**
     * Copies the content from $this->source directory to $this->target
     */
    public function copy()
    {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir($this->target);

        $directoryIterator = new \RecursiveDirectoryIterator($this->source, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $targetDir = $this->target . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                $fileSystem->mkdir($targetDir);
            } else {
                $targetFilename = $this->target . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                $fileSystem->copy($item, $targetFilename);
            }
        }
    }

    /**
     * Removes $this->target directory
     */
    public function delete()
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove($this->target);
    }
}
