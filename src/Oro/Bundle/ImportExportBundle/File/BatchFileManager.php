<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;

/**
 * Provides a functionality to split a file into chunks and merge files into a summary file.
 */
class BatchFileManager
{
    /**
     * @var int
     */
    protected $sizeOfBatch;

    /**
     * @var AbstractFileReader
     */
    protected $reader;

    /**
     * @var FileStreamWriter
     */
    protected $writer;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var array
     */
    protected $configurationOptions = [];

    /**
     * @param FileManager $fileManager
     * @param integer $sizeOfBatch
     */
    public function __construct(FileManager $fileManager, $sizeOfBatch)
    {
        $this->fileManager = $fileManager;
        $this->sizeOfBatch = $sizeOfBatch;
    }

    public function setReader(AbstractFileReader $reader)
    {
        $this->reader = $reader;
    }

    public function setWriter(FileStreamWriter $writer)
    {
        $this->writer = $writer;
    }

    public function setConfigurationOptions(array $options)
    {
        $this->configurationOptions = $options;
    }

    /**
     * Splits a file into chunks and returns the chunk file names.
     */
    public function splitFile($pathFile)
    {
        if (null === $this->writer || null === $this->reader) {
            throw new InvalidConfigurationException('Reader and Writer must be configured.');
        }

        $readerContext = new Context(array_merge(
            [Context::OPTION_FILE_PATH => $pathFile],
            $this->configurationOptions
        ));
        $this->reader->initializeByContext($readerContext);

        $extension = pathinfo($pathFile, PATHINFO_EXTENSION);
        $batchSize = $readerContext->getOption(Context::OPTION_BATCH_SIZE) ?: $this->sizeOfBatch;

        $files = [];
        $header = null;
        $items = [];
        try {
            while ($item = $this->reader->read($readerContext)) {
                $header = $header ?: $this->reader->getHeader();
                $items[] = $item;
                if (\count($items) === $batchSize) {
                    $files[] = $this->writeBatch($items, $header, $extension);
                    $items = [];
                }
            }
            if (\count($items) > 0) {
                $files[] = $this->writeBatch($items, $header, $extension);
            }
        } finally {
            $this->reader->close();
        }

        return $files;
    }

    /**
     * Merges files into a summary file.
     */
    public function mergeFiles(array $files, $summaryFile)
    {
        if (null === $this->writer || null === $this->reader) {
            throw new InvalidConfigurationException('Reader and Writer must be configured.');
        }

        $writerContext = null;
        try {
            foreach ($files as $file) {
                $readerContext = new Context([Context::OPTION_FILE_PATH => $file]);
                $this->reader->initializeByContext($readerContext);
                try {
                    $items = [];
                    while ($item = $this->reader->read($readerContext)) {
                        if (null === $writerContext) {
                            $writerContext = new Context([
                                Context::OPTION_FILE_PATH => $summaryFile,
                                Context::OPTION_HEADER => $this->reader->getHeader(),
                                Context::OPTION_FIRST_LINE_IS_HEADER => true
                            ]);
                            $this->writer->setImportExportContext($writerContext);
                        }
                        $items[] = $item;
                        if (\count($items) === $this->sizeOfBatch) {
                            $this->writer->write($items);
                            $items = [];
                        }
                    }
                    if (\count($items) > 0) {
                        $this->writer->write($items);
                    }
                } finally {
                    $this->reader->close();
                }
            }
        } finally {
            $this->writer->close();
        }
    }

    private function writeBatch(array $items, ?array $header, string $extension): string
    {
        $batchFileName = FileManager::generateUniqueFileName($extension);
        $batchFilePath = FileManager::generateTmpFilePath($batchFileName);
        $writerContext = new Context(array_merge(
            [
                Context::OPTION_FILE_PATH => $batchFilePath,
                Context::OPTION_HEADER => $header
            ],
            $this->configurationOptions
        ));
        $this->writer->setImportExportContext($writerContext);
        try {
            try {
                $this->writer->write($items);
            } finally {
                $this->writer->close();
            }
            $this->fileManager->writeFileToStorage($batchFilePath, $batchFileName);
        } finally {
            @unlink($batchFilePath);
        }

        return $batchFileName;
    }
}
