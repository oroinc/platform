<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\XLSX\RowIterator;
use Box\Spout\Reader\XLSX\Sheet;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Corresponds for reading xlsx file line by line using context passed
 */
class XlsxFileReader extends AbstractFileReader
{
    /** @var ReaderInterface */
    private $fileReader;

    /** @var RowIterator */
    private $rowIterator;

    /**
     * {@inheritdoc}
     */
    public function read($context = null): ?array
    {
        if (!$context instanceof ContextInterface) {
            $context = $this->getContext();
        }

        $data = $this->readRow($context);
        if (!array_filter($data)) {
            if ($this->isEof()) {
                $this->rowIterator->rewind();
                $this->header = null;
                return null;
            } else {
                return [];
            }
        }

        return $this->normalizeRow($data);
    }

    private function readRow(ContextInterface $context): array
    {
        if ($this->isEof()) {
            return [];
        }

        $context->incrementReadOffset();
        $data = $this->rowIterator->current();
        $this->rowIterator->next();
        $context->incrementReadCount();
        return $data->toArray();
    }

    private function isEof(): bool
    {
        if (!$this->rowIterator->valid()) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeByContext(ContextInterface $context): void
    {
        parent::initializeByContext($context);

        $this->fileReader = ReaderFactory::createFromType(Type::XLSX);
        $this->fileReader->open($this->fileInfo->getPathname());

        $sheetIterator = $this->fileReader->getSheetIterator();
        $sheetIterator->rewind();

        /** @var Sheet $sheet */
        $sheet = $sheetIterator->current();
        $this->rowIterator = $sheet->getRowIterator();
        $this->rowIterator->rewind();

        if ($this->firstLineIsHeader && !$this->header) {
            $this->header = $this->readRow($context);
        }
    }

    public function close()
    {
        $this->rowIterator = null;
        if ($this->fileReader) {
            $this->fileReader->close();
        }
        parent::close();
    }
}
