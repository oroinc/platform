<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer as Writer;
use Psr\SimpleCache\CacheInterface;

/**
 * Write XLSX to file
 */
abstract class XlsxFileStreamWriter extends FileStreamWriter
{
    /** @var bool */
    protected $firstLineIsHeader = true;

    /** @var Spreadsheet */
    private $spreadsheet;

    /** @var int */
    private $currentRow = 0;

    /** @var CacheInterface */
    private $cache;

    /**
     * XlsxFileStreamWriter constructor.
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
        Settings::setCache($cache);
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet(): Spreadsheet
    {
        if (!$this->spreadsheet) {
            $this->spreadsheet = new Spreadsheet();
        }

        return $this->spreadsheet;
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

        $this->getSpreadsheet()
            ->getActiveSheet()
            ->fromArray($writeArray, null, 'A'.++$this->currentRow);

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
        if ($this->spreadsheet) {
            $writer = new Writer\Xlsx($this->spreadsheet);
            $writer->save($this->filePath);

            $this->spreadsheet = null;
            $this->header = null;
            $this->currentRow = 0;
            $this->cache->clear();
        }
    }
}
