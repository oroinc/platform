<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use PhpOffice\PhpSpreadsheet\Reader as Reader;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;
use Psr\SimpleCache\CacheInterface;

/**
 * Corresponds for reading xlsx file line by line using context passed
 */
class XlsxFileReader extends AbstractFileReader
{
    /** @var CacheInterface */
    private $cache;

    /** @var RowIterator */
    private $rowIterator;

    /**
     * @param ContextRegistry $contextRegistry
     * @param CacheInterface $cache
     */
    public function __construct(ContextRegistry $contextRegistry, CacheInterface $cache)
    {
        parent::__construct($contextRegistry);
        $this->cache = $cache;
    }

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

    /**
     * @param ContextInterface $context
     * @return array
     */
    private function readRow(ContextInterface $context): array
    {
        $data = [];

        if (!$this->isEof()) {
            $row = $this->rowIterator->current();
            $cellIterator = $row->getCellIterator('A', $row->getWorksheet()->getHighestDataColumn());

            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }

            $this->rowIterator->next();
            $context->incrementReadOffset();
            $context->incrementReadCount();
        }

        return $data;
    }

    /**
     * @return bool
     */
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

        Settings::setCache($this->cache);

        $fileReader = new Reader\Xlsx();
        $fileReader->setReadDataOnly(true);

        $activeSheet = $fileReader->load($this->fileInfo->getPathname())->getActiveSheet();
        $this->rowIterator = $activeSheet->getRowIterator(1, $activeSheet->getHighestDataRow());

        if ($this->firstLineIsHeader && !$this->header) {
            $this->header = $this->readRow($context);
        }
    }

    public function close()
    {
        $this->rowIterator = null;
        parent::close();
    }
}
