<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

/**
 * Provides a set of methods to manage errors occurred when processing a batch operation.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ErrorManager
{
    private const ITEM_FILE_NAME = 0;
    private const ITEM_FILE_INDEX = 1;
    private const ITEM_ERRORS_COUNT = 2;

    private const ATTR_ITEM_INDEX = 'i';
    private const ATTR_STATUS_CODE = 'c';
    private const ATTR_CODE = 'e';
    private const ATTR_TITLE = 't';
    private const ATTR_DETAIL = 'd';
    private const ATTR_SOURCE = 's';
    private const ATTR_POINTER = 'p';
    private const ATTR_PROPERTY_PATH = 'pp';
    private const ATTR_PARAMETER = 'pr';

    private FileNameProvider $fileNameProvider;
    private FileLockManager $fileLockManager;
    private LoggerInterface $logger;

    public function __construct(
        FileNameProvider $fileNameProvider,
        FileLockManager $fileLockManager,
        LoggerInterface $logger
    ) {
        $this->fileNameProvider = $fileNameProvider;
        $this->fileLockManager = $fileLockManager;
        $this->logger = $logger;
    }

    /**
     * @param FileManager $fileManager The manager responsible to work with input and output files
     * @param int         $operationId The ID of the asynchronous operation
     *
     * @return int
     */
    public function getTotalErrorCount(FileManager $fileManager, int $operationId): int
    {
        $errorsIndex = $this->loadErrorIndex($fileManager, $operationId);
        if ($errorsIndex) {
            return $this->computeTotalErrorCount($errorsIndex);
        }

        return 0;
    }

    /**
     * @param FileManager $fileManager The manager responsible to work with input and output files
     * @param int         $operationId The ID of the asynchronous operation
     * @param int         $offset      The index of the first error
     * @param int         $limit       The maximum number of errors to be returned
     *
     * @return BatchError[]
     */
    public function readErrors(FileManager $fileManager, int $operationId, int $offset, int $limit): array
    {
        /** @var BatchError[] $errors */
        $errors = [];

        // the number of items to cut from the beginning of the result.
        // used only if the offset being set.
        $skipNumber = 0;

        // errors loading will not start until this flag is true
        $skipOffset = (bool)$offset;

        $errorsCount = 0;
        $errorsIndex = $this->loadErrorIndex($fileManager, $operationId);
        if ($errorsIndex) {
            $totalErrorsCount = $this->computeTotalErrorCount($errorsIndex);
            if ($totalErrorsCount < $offset) {
                // nothing to do in case the total number of errors is less than the offset
                return [];
            }

            foreach ($errorsIndex as $errorIndexKey => [$fileName, $fileIndex, $count]) {
                $errorsCount += $count;

                // avoid unnecessary file loading and parsing if the offset being set
                if ($skipOffset) {
                    if ($errorsCount <= $offset) {
                        continue;
                    }
                    $skipNumber = $count - ($errorsCount - $offset);
                    $skipOffset = false;
                }

                $serializedErrors = JsonUtil::decode($fileManager->getFileContent($fileName));
                $index = 0;
                foreach ($serializedErrors as $serializedError) {
                    $errors[] = $this->deserializeError(
                        sprintf('%s-%s-%s', $operationId, $fileIndex + 1, $errorIndexKey + 1 + $index),
                        $serializedError
                    );
                    $index++;
                }

                if (\count($errors) - $skipNumber >= $limit) {
                    return \array_slice($errors, $skipNumber, $limit);
                }
            }
        }

        return \array_slice($errors, $skipNumber, $limit);
    }

    /**
     * @param FileManager  $fileManager The manager responsible to work with input and output files
     * @param int          $operationId The ID of the asynchronous operation
     * @param BatchError[] $errors      The list of errors to be saved
     * @param ChunkFile    $chunkFile   The information about the file contains the input data
     */
    public function writeErrors(FileManager $fileManager, int $operationId, array $errors, ChunkFile $chunkFile): void
    {
        if (!$errors) {
            return;
        }

        $chunkFileName = $chunkFile->getFileName();
        $chunkFileIndex = $chunkFile->getFileIndex();
        $chunkErrorsFileName = $this->fileNameProvider->getChunkErrorsFileName($chunkFileName);
        $serializedErrors = $this->serializeErrors($errors);

        $indexFileName = $this->fileNameProvider->getErrorIndexFileName($operationId);
        $lockFileName = $this->fileNameProvider->getLockFileName($indexFileName);
        if (!$this->fileLockManager->acquireLock($lockFileName)) {
            $this->logger->error(sprintf(
                'Failed to update the errors index file for the "%s" chunk file because the lock cannot be acquired.',
                $chunkFileName
            ));

            return;
        }

        try {
            $indexFile = $fileManager->getFile($indexFileName, false);
            $errorsIndex = null === $indexFile
                ? []
                : JsonUtil::decode($indexFile->getContent());
            $updated = false;
            if ($chunkFileIndex < 0) {
                $foundDataItemIndex = $this->searchInErrorsIndex($errorsIndex, $chunkErrorsFileName);
                if (null !== $foundDataItemIndex) {
                    $errorsIndex[$foundDataItemIndex][self::ITEM_ERRORS_COUNT] += \count($errors);
                    $serializedErrors = array_merge(
                        JsonUtil::decode($fileManager->getFileContent($chunkErrorsFileName)),
                        $serializedErrors
                    );
                    $updated = true;
                }
            }
            if (!$updated) {
                $errorsIndex[] = [$chunkErrorsFileName, $chunkFileIndex, \count($errors)];
            }
            $fileManager->writeToStorage(JsonUtil::encode($serializedErrors), $chunkErrorsFileName);
            $fileManager->writeToStorage(JsonUtil::encode($errorsIndex), $indexFileName);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to update the errors index file for the "%s" chunk file.', $chunkFileName),
                ['exception' => $e]
            );
        } finally {
            $this->fileLockManager->releaseLock($lockFileName);
        }
    }

    /**
     * @param array  $errorsIndex [[file name, file index, errors count], ...]
     * @param string $fileName
     *
     * @return int|null
     */
    private function searchInErrorsIndex(array $errorsIndex, string $fileName): ?int
    {
        foreach ($errorsIndex as $itemIndex => $item) {
            if ($item[self::ITEM_FILE_NAME] === $fileName) {
                return $itemIndex;
            }
        }

        return null;
    }

    /**
     * @param FileManager $fileManager
     * @param int         $operationId
     *
     * @return array [[file name, file index, errors count], ...]
     */
    private function loadErrorIndex(FileManager $fileManager, int $operationId): array
    {
        $indexFileName = $this->fileNameProvider->getErrorIndexFileName($operationId);
        if (!$fileManager->hasFile($indexFileName)) {
            return [];
        }

        $data = [];
        try {
            $data = JsonUtil::decode($fileManager->getFileContent($indexFileName));
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Failed to read the errors index file "%s".', $indexFileName),
                ['exception' => $e]
            );
        }

        if ($data) {
            usort($data, function ($a, $b) {
                return $a[self::ITEM_FILE_INDEX] - $b[self::ITEM_FILE_INDEX];
            });
        }

        return $data;
    }

    /**
     * Returns total number of errors in log
     *
     * @param array $errorsIndex [[file name, file index, errors count], ...]
     *
     * @return int
     */
    private function computeTotalErrorCount(array $errorsIndex): int
    {
        $totalErrorsCount = 0;
        foreach ($errorsIndex as $item) {
            $totalErrorsCount += $item[self::ITEM_ERRORS_COUNT];
        }

        return $totalErrorsCount;
    }

    /**
     * @param BatchError[] $errors
     *
     * @return array
     */
    private function serializeErrors(array $errors): array
    {
        $serializedErrors = [];
        foreach ($errors as $error) {
            $serializedErrors[] = $this->serializeError($error);
        }

        return $serializedErrors;
    }

    private function serializeError(BatchError $error): array
    {
        $data = [];
        if ($error->getItemIndex() !== null) {
            $data[self::ATTR_ITEM_INDEX] = $error->getItemIndex();
        }
        if ($error->getStatusCode() !== null) {
            $data[self::ATTR_STATUS_CODE] = $error->getStatusCode();
        }
        if ($error->getCode() !== null) {
            $data[self::ATTR_CODE] = $error->getCode();
        }
        if ($error->getTitle() !== null) {
            $data[self::ATTR_TITLE] = $error->getTitle();
        }
        if ($error->getDetail() !== null) {
            $data[self::ATTR_DETAIL] = $error->getDetail();
        }
        if ($error->getSource() !== null) {
            $sourceData = $this->serializeErrorSource($error->getSource());
            if ($sourceData) {
                $data[self::ATTR_SOURCE] = $sourceData;
            }
        }

        return $data;
    }

    private function deserializeError(string $id, array $data): BatchError
    {
        $error = new BatchError();
        $error->setId($id);
        if (isset($data[self::ATTR_ITEM_INDEX])) {
            $error->setItemIndex($data[self::ATTR_ITEM_INDEX]);
        }
        if (isset($data[self::ATTR_STATUS_CODE])) {
            $error->setStatusCode($data[self::ATTR_STATUS_CODE]);
        }
        if (isset($data[self::ATTR_CODE])) {
            $error->setCode($data[self::ATTR_CODE]);
        }
        if (isset($data[self::ATTR_TITLE])) {
            $error->setTitle($data[self::ATTR_TITLE]);
        }
        if (isset($data[self::ATTR_DETAIL])) {
            $error->setDetail($data[self::ATTR_DETAIL]);
        }
        if (isset($data[self::ATTR_SOURCE])) {
            $error->setSource($this->deserializeErrorSource($data[self::ATTR_SOURCE]));
        }

        return $error;
    }

    private function serializeErrorSource(ErrorSource $source): array
    {
        $data = [];
        if ($source->getPointer() !== null) {
            $data[self::ATTR_POINTER] = $source->getPointer();
        }
        if ($source->getPropertyPath() !== null) {
            $data[self::ATTR_PROPERTY_PATH] = $source->getPropertyPath();
        }
        if ($source->getParameter() !== null) {
            $data[self::ATTR_PARAMETER] = $source->getParameter();
        }

        return $data;
    }

    private function deserializeErrorSource(array $data): ErrorSource
    {
        $source = new ErrorSource();
        if (isset($data[self::ATTR_POINTER])) {
            $source->setPointer($data[self::ATTR_POINTER]);
        }
        if (isset($data[self::ATTR_PROPERTY_PATH])) {
            $source->setPropertyPath($data[self::ATTR_PROPERTY_PATH]);
        }
        if (isset($data[self::ATTR_PARAMETER])) {
            $source->setParameter($data[self::ATTR_PARAMETER]);
        }

        return $source;
    }
}
