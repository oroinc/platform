<?php

namespace Oro\Bundle\ImportExportBundle\Splitter;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Item\ParseException;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

class SplitterCsvFile implements SplitterInterface
{
    /**
     * @var CsvFileReader
     */
    protected $csvReader;

    /**
     * @var integer
     */
    protected $sizeOfBatch;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param $csvReader CsvFileReader
     * @param $sizeOfBatch integer
     * @param $fileManager FileManager
     */
    public function __construct(CsvFileReader $csvReader, $sizeOfBatch, FileManager $fileManager)
    {
        $this->csvReader = $csvReader;
        $this->sizeOfBatch = $sizeOfBatch;
        $this->fileManager = $fileManager;
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
        $this->errors = [];
        $filename = basename($pathFile);

        $files = [];
        try {
            $data = $this->readFile($pathFile);
            $numberOfChunk = 0;
            $dataOfBatch = array_chunk($data, $this->sizeOfBatch);
            foreach ($dataOfBatch as $chunk) {
                $files[] = $this->createBatchFile($chunk, $filename, ++$numberOfChunk);
            }
        } catch (InvalidItemException $e) {
            $this->addError($e);
        } catch (ParseException $e) {
            $this->addError($e);
        }

        return $files;
    }

    protected function createBatchFile(array $data, $fileName, $numberOfChunk)
    {
        $fileName = sprintf('chunk_%s_%s', $numberOfChunk, $fileName);
        $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
        $file = new \SplFileObject($filePath, 'w+');
        $firstRow = current($data);
        $file->fputcsv(array_keys($firstRow));

        foreach ($data as $row) {
            $file->fputcsv($row);
        }

        $this->fileManager->saveFileToStorage($file->getFileInfo(), $fileName, true);
        unlink($filePath);

        return $fileName;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    private function addError(\Exception $e)
    {
        $this->errors[] = $e->getMessage();
    }
}
