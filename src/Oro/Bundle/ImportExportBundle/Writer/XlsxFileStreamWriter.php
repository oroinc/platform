<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Write XLSX to file
 */
abstract class XlsxFileStreamWriter extends FileStreamWriter
{
    /** @var bool */
    protected $firstLineIsHeader = true;

    /** @var int */
    private $currentRow = 0;

    /** @var WriterInterface */
    private $writer;

    public function getWriter(): WriterInterface
    {
        if (!$this->writer) {
            $this->writer = new XLSXWriter();
            $this->writer->openToFile($this->filePath);
        }

        return $this->writer;
    }

    #[\Override]
    public function write(array $items): void
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = self::createRowFromArray($item);
        }
        // write a header if needed
        if ($this->firstLineIsHeader && $this->currentRow === 0) {
            if (!$this->header && count($items) > 0) {
                $this->header = array_keys($items[0]);
            }

            if ($this->header) {
                $header = self::createRowFromArray($this->header);
                array_unshift($rows, $header);
            }
        }
        $this->getWriter()->addRows($rows);

        $this->currentRow += count($rows) - 1;
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context): void
    {
        if ($context->hasOption('firstLineIsHeader')) {
            $this->firstLineIsHeader = (bool)$context->getOption('firstLineIsHeader');
        }

        if ($context->hasOption('header')) {
            $this->header = $context->getOption('header');
        }
    }

    /**
     * Write to file on close.
     * A little hacky but direct write is not possible because you cannot append data directly
     */
    #[\Override]
    public function close(): void
    {
        if ($this->writer) {
            $this->writer->close();
            $this->header = null;
            $this->currentRow = 0;
        }
    }

    protected static function createRowFromArray(array $cellValues = [], Style $rowStyle = null): Row
    {
        $cells = array_map(function ($cellValue) {
            return Cell::fromValue($cellValue);
        }, $cellValues);

        return new Row($cells, $rowStyle);
    }
}
