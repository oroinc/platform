<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Liuggio\ExcelBundle\Factory as ExcelFactory;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

class XlsxFileReader extends AbstractFileReader
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
     * @var boolean
     */
    protected $rewound = false;

    /**
     * @var \PHPExcel_Worksheet_RowIterator
     */
    protected $rowIterator;

    /**
     * @param ContextRegistry $contextRegistry
     * @param ExcelFactory $phpExcel
     */
    public function __construct(ContextRegistry $contextRegistry, ExcelFactory $phpExcel)
    {
        $this->phpExcel = $phpExcel;
        parent::__construct($contextRegistry);
    }

    /**
     * {@inheritdoc}
     */
    public function read($context = null)
    {
        if (! $this->rowIterator instanceof \PHPExcel_Worksheet_RowIterator) {
            $this->initRowIterator();
        }

        if (! $context instanceof ContextInterface) {
            $context = $this->getContext();
        }

        if (!$this->rewound) {
            $this->rowIterator->resetStart();
            $this->rewound = true;
        }
        if ($data = $this->readLine($context)) {
            if ($this->firstLineIsHeader) {
                if ($context->getReadCount() === 1) {
                    $this->header || $this->header = $data;
                    $data = $this->readLine($context);
                }
            }

            if ($data && count($this->header) !== count($data)) {
                throw new InvalidItemException(
                    sprintf(
                        'Expecting to get %d columns, actually got %d',
                        count($this->header),
                        count($data)
                    ),
                    $data
                );
            }
        }

        return $data;
    }

    /**
     * @param ContextInterface $context
     * @return array|null
     */
    public function readLine(ContextInterface $context)
    {
        $data = null;
        if ($this->rowIterator->valid()) {
            foreach ($this->rowIterator->current()->getCellIterator() as $cell) {
                $data[] = $cell->getValue();
            }
            $context->incrementReadOffset();
            $context->incrementReadCount();
            $this->rowIterator->next();
        } else {
            $this->header = null;
        }

        return $data;
    }

    /**
     * @return \PHPExcel
     */
    public function getFile()
    {
        if ($this->excelObj) {
            return $this->excelObj;
        }

        \PHPExcel_Settings::setCacheStorageMethod(
            \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
            ['memoryCacheSize' => '512M']
        );

        $fileName = $this->fileInfo->getPathname();

        return $this->excelObj = $this->phpExcel->createPHPExcelObject($fileName);
    }

    /**
     * @param null|string $filePath
     */
    protected function initRowIterator($filePath = null)
    {
        if (! $filePath) {
            $filePath = $this->fileInfo->getPathname();
        }

        $this->header = null;
        $excelObj = $this->phpExcel->createPHPExcelObject($filePath);
        $sheet = $excelObj->getActiveSheet();
        $this->rowIterator = $sheet->getRowIterator();
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);

        if ($context->hasOption('firstLineIsHeader')) {
            $this->firstLineIsHeader = (bool)$context->getOption('firstLineIsHeader');
        }

        if ($context->hasOption('header')) {
            $this->header = $context->getOption('header');
        }

        $this->initRowIterator($context->getOption('filePath'));
    }
}
