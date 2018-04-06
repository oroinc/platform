<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Liuggio\ExcelBundle\Factory as ExcelFactory;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

abstract class XlsxFileStreamWriter extends FileStreamWriter
{
    /**
     * @var bool
     */
    protected $firstLineIsHeader = true;

    /**
     * @var ExcelFactory
     */
    protected $phpExcel;

    /**
     * @var \PHPExcel
     */
    protected $excelObj;

    /**
     * @var int
     */
    protected $currentRow = 0;

    /**
     * @param ExcelFactory $phpExcel
     */
    public function __construct(ExcelFactory $phpExcel)
    {
        $this->phpExcel = $phpExcel;
    }

    /**
     * @return \PHPExcel
     */
    public function createPHPExcelObject()
    {
        if ($this->excelObj) {
            return $this->excelObj;
        }

        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '512M']
        );

        return $this->excelObj = $this->phpExcel->createPHPExcelObject();
    }

    /**
     * @inheritdoc
     */
    public function write(array $items)
    {
        $writeArray = [];
        // write a header if needed
        if ($this->firstLineIsHeader && ! $this->excelObj) {
            if (!$this->header && count($items) > 0) {
                $this->header = array_keys($items[0]);
            }
            if ($this->header) {
                $writeArray[] = $this->header;
            }
        }

        $this->createPHPExcelObject();
        $writeArray = array_merge($writeArray, $items);
        $sheet = $this->excelObj->getActiveSheet();
        $sheet->fromArray($writeArray, null, 'A'.++$this->currentRow);
        $this->currentRow += count($writeArray) - 1;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
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
    public function close()
    {
        if ($this->excelObj) {
            $this->phpExcel->createWriter($this->excelObj, 'Excel2007')->save($this->filePath);
            $this->excelObj = null;
            $this->header = null;
            $this->currentRow = 0;
        }
    }
}
