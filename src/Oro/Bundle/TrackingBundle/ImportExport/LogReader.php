<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

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
        $context = $this->getContext();

        if (empty($this->data)) {
            $context = $this->getContext();
            $file    = $context->getOption('file');

            $content    = file_get_contents($file);
            $this->data = explode(PHP_EOL, $content);
        }

        $item = $this->data[$context->getReadCount()];
        $context->incrementReadCount();

        return json_decode($item, true);
    }
}
