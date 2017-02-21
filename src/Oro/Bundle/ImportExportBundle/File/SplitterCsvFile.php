<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

class SplitterCsvFile
{
    /**
     * @var CsvFileReader
     */
    protected $csvReader;

    /**
     * @var string
     */
    protected $storagePath;

    /**
     * @var integer
     */
    protected $sizeOfBatch;

    /**
     * @param $csvReader CsvFileReader
     * @param $storagePath string
     * @param $sizeOfBatch integer
     */
    public function __construct(CsvFileReader $csvReader, $storagePath, $sizeOfBatch)
    {
        $this->csvReader = $csvReader;
        $this->storagePath = $storagePath;
        $this->sizeOfBatch = $sizeOfBatch;
    }

    protected function readFile($pathFile)
    {
        $context = new Context(['filePath' => $pathFile]);
        $this->csvReader->initializeByContext($context);

        $data = [];
        while ($row = $this->csvReader->read($context)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param $pathFile string
     * @return array
     */
    public function getSplittedFilesNames($pathFile)
    {
        $filename = basename($pathFile);

        $files = [];
        $data = $this->readFile($pathFile);
        if (count($data) > $this->sizeOfBatch) {
            $numberOfChunk = 0;
            $dataOfBatch = array_chunk($data, $this->sizeOfBatch);
            foreach ($dataOfBatch as $chunk) {
                $files[] = $this->createBatchFile($chunk, $filename, ++$numberOfChunk);
            }
        } else {
            $files = [$pathFile];
        }

        return $files;
    }

    protected function createBatchFile(array $data, $filename, $numberOfChunk)
    {
        $fileName = sprintf('%s/chunk_%s_%s', $this->storagePath, $numberOfChunk, $filename);
        $file = new \SplFileObject($fileName, 'w+');
        $firstRow = current($data);
        $file->fputcsv(array_keys($firstRow));
        foreach ($data as $row) {
            $file->fputcsv($row);
        }

        return $fileName;
    }
}
