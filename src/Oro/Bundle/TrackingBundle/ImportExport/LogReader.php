<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;

class LogReader extends AbstractReader
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * @var array
     */
    protected $data;

    /**
     * @param string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $fs     = new Filesystem();
        $finder = new Finder();

        if (!$fs->exists($this->directory)) {
            throw new \InvalidArgumentException(
                sprintf('Directory "%s" does not exists', $this->directory)
            );
        }

        $finder->files()->in($this->directory);

        if (!$finder->count()) {
            throw new \InvalidArgumentException(
                sprintf('Directory "%s" is empty', $this->directory)
            );
        }

        $context = $this->getContext();

        /* @todo: skip current one */
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            break;
        }

        if ($content = $file->getContents()) {
            $this->data = explode(PHP_EOL, $content);
        }
        $context->incrementReadOffset();
        $item = $this->data[$context->getReadOffset() - 1];
        $context->incrementReadCount();

        return json_decode($item, true);
    }
}
