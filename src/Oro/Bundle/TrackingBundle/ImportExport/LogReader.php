<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;

class LogReader extends AbstractReader
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        if ($this->file->valid()) {
            $this->file->seek($this->getContext()->getReadCount());
            $line = rtrim($this->file->current(), PHP_EOL);
            $this->getContext()->incrementReadCount();

            return json_decode($line, true);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if (!$context->hasOption('file')) {
            throw new InvalidConfigurationException(
                'Configuration reader must contain "file".'
            );
        } else {
            $this->file = new \SplFileObject(
                $context->getOption('file')
            );
        }
    }
}
