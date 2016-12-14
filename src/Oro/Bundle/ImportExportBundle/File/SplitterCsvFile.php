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

    const SIZE_OF_BATCH = 100;

    public function __construct($csvReader, $storagePath)
    {
        $this->csvReader = $csvReader;
        $this->storagePath = $storagePath;
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
    public function getSplitFiles($pathFile)
    {
        $filename = basename($pathFile);

        $files = [];
        $data = $this->readFile($pathFile);
        if (count($data) > self::SIZE_OF_BATCH) {
            $numberOfChunk = 0;
            $dataOfBatch = array_chunk($data, self::SIZE_OF_BATCH);
            foreach ($dataOfBatch as $chunk) {
                $files[] = $this->createBatchFile($chunk, $filename, ++$numberOfChunk);
            }
        } else {
            $files = [$pathFile];
        }

        $this->csvReader->resetFile();

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
