<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\Context;
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
     * @var string
     */
    protected $escape;

    /**
     * @var bool
     */
    protected $firstLineIsHeader = true;

    /**
     * @var resource
     */
    protected $fileHandle;

    public function __construct()
    {
        /**
         * According to RFC4180 for CSV (https://tools.ietf.org/html/rfc4180)
         * there's no special meaning for backslash character. As data for import/export is usually meant
         * to be treated "as is" it was decided to set escape symbol as 0x0 character.
         * This solves a problem when csv field contains single backslash character.
         *
         * If one wants to use other escape symbol then he or she needs to pass it via "escape" option
         * and provide needed changes for correct csv reading/writing.
         */
        $this->escape = chr(0);
    }

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
        $result = fputcsv($this->getFile(), $fields, $this->delimiter, $this->enclosure, $this->escape);
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
            $this->header = null;
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
        if ($context->hasOption(Context::OPTION_DELIMITER)) {
            $this->delimiter = $context->getOption(Context::OPTION_DELIMITER);
        }

        if ($context->hasOption(Context::OPTION_ENCLOSURE)) {
            $this->enclosure = $context->getOption(Context::OPTION_ENCLOSURE);
        }

        if ($context->hasOption(Context::OPTION_ESCAPE)) {
            $this->escape = $context->getOption(Context::OPTION_ESCAPE);
        }

        if ($context->hasOption(Context::OPTION_FIRST_LINE_IS_HEADER)) {
            $this->firstLineIsHeader = (bool)$context->getOption(Context::OPTION_FIRST_LINE_IS_HEADER);
        }

        if ($context->hasOption(Context::OPTION_HEADER)) {
            $this->header = $context->getOption(Context::OPTION_HEADER);
        }
    }
}
