<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;

abstract class CsvFileStreamWriter extends FileStreamWriter
{
    /**
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string
     */
    protected $enclosure = '"';

    /**
     * @var bool
     */
    protected $firstLineIsHeader = true;

    /**
     * @var array
     */
    protected $header;

    /**
     * @var resource
     */
    protected $fileHandle;

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        // write a header if needed
        if (!$this->fileHandle && $this->firstLineIsHeader) {
            if (!$this->header && count($items) > 0) {
                $this->header = array_keys($items[0]);
            }
            if ($this->header) {
                $this->writeHeader();
            }
        }

        // write items
        foreach ($items as $item) {
            $this->writeLine($item);
        }
        $this->flushOutput();
    }

    /**
     * Write CSV file header.
     */
    protected function writeHeader()
    {
        $this->writeLine($this->header);
    }

    /**
     * Write CSV line.
     *
     * @param array $fields
     * @throws RuntimeException
     */
    protected function writeLine(array $fields)
    {
        $result = fputcsv($this->getFile(), $fields, $this->delimiter, $this->enclosure);
        if ($result === false) {
            throw new RuntimeException('An error occurred while writing to the csv.');
        }
    }

    /**
     * Get file resource.
     *
     * @return resource A file pointer resource
     */
    protected function getFile()
    {
        if (!$this->fileHandle) {
            $this->fileHandle = $this->open();
        }

        return $this->fileHandle;
    }

    /**
     * Opens a file.
     *
     * @return resource A file pointer resource
     */
    abstract protected function open();

    /**
     * Closes an open file.
     */
    public function close()
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Flushes the output to a file.
     */
    protected function flushOutput()
    {
        if ($this->fileHandle) {
            fflush($this->fileHandle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        if ($context->hasOption('delimiter')) {
            $this->delimiter = $context->getOption('delimiter');
        }

        if ($context->hasOption('enclosure')) {
            $this->enclosure = $context->getOption('enclosure');
        }

        if ($context->hasOption('firstLineIsHeader')) {
            $this->firstLineIsHeader = (bool)$context->getOption('firstLineIsHeader');
        }

        if ($context->hasOption('header')) {
            $this->header = $context->getOption('header');
        }
    }
}
