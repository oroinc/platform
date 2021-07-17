<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Box\Spout\Common\Type;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Box\Spout\Writer\WriterInterface;
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
            $this->writer = WriterFactory::createFromType(Type::XLSX);
            $this->writer->openToFile($this->filePath);
        }

        return $this->writer;
    }

    /**
     * @inheritdoc
     */
    public function write(array $items): void
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = WriterEntityFactory::createRowFromArray($item);
        }
        // write a header if needed
        if ($this->firstLineIsHeader && $this->currentRow === 0) {
            if (!$this->header && count($items) > 0) {
                $this->header = array_keys($items[0]);
            }

            if ($this->header) {
                $header = WriterEntityFactory::createRowFromArray($this->header);
                array_unshift($rows, $header);
            }
        }
        $this->getWriter()->addRows($rows);

        $this->currentRow += count($rows) - 1;
    }

    /**
     * {@inheritdoc}
     */
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
    public function close(): void
    {
        if ($this->writer) {
            $this->writer->close();
            $this->header = null;
            $this->currentRow = 0;
        }
    }
}
