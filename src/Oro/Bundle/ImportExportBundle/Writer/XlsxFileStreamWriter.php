<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
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

    /**
     * @return WriterInterface
     */
    public function getWriter(): WriterInterface
    {
        if (!$this->writer) {
            $this->writer = WriterFactory::create(Type::XLSX);
            $this->writer->openToFile($this->filePath);
        }

        return $this->writer;
    }

    /**
     * @inheritdoc
     */
    public function write(array $items): void
    {
        $writeArray = $items;
        // write a header if needed
        if ($this->firstLineIsHeader && $this->currentRow === 0) {
            if (!$this->header && count($items) > 0) {
                $this->header = array_keys($items[0]);
            }

            if ($this->header) {
                array_unshift($writeArray, $this->header);
            }
        }

        $this->getWriter()->addRows($writeArray);

        $this->currentRow += count($writeArray) - 1;
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
