<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorInterface;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

/**
 * Provides methods to manage the included items map.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class IncludeMapManager
{
    private const FILES = 'files';
    private const ITEMS = 'items';

    private const FILE_NAME = 0;
    private const FILE_SECTION_NAME = 1;
    private const FILE_FIRST_ITEM_OFFSET = 2;
    private const ITEM_INDEX = 1;

    private ItemKeyBuilder $itemKeyBuilder;
    private FileNameProvider $fileNameProvider;
    private FileLockManager $fileLockManager;
    private LoggerInterface $logger;
    /** @var int limit 100 + waitBetweenAttempts 100 equals to the acquire lock timeout 10 seconds */
    private int $readLockAttemptLimit = 100;
    private int $readLockWaitBetweenAttempts = 100;
    /** @var int limit 3600 + waitBetweenAttempts 50 equals to the acquire lock timeout 3 minutes */
    private int $moveToProcessedLockAttemptLimit = 3600;
    private int $moveToProcessedLockWaitBetweenAttempts = 50;

    public function __construct(
        ItemKeyBuilder $itemKeyBuilder,
        FileNameProvider $fileNameProvider,
        FileLockManager $fileLockManager,
        LoggerInterface $logger
    ) {
        $this->itemKeyBuilder = $itemKeyBuilder;
        $this->fileNameProvider = $fileNameProvider;
        $this->fileLockManager = $fileLockManager;
        $this->logger = $logger;
    }

    /**
     * Sets the max number of attempts to acquire the lock for the get included items operation.
     */
    public function setReadLockAttemptLimit(int $limit): void
    {
        $this->readLockAttemptLimit = $limit;
    }

    /**
     * Sets the time in milliseconds between acquire the lock attempts for the get included items operation.
     */
    public function setReadLockWaitBetweenAttempts(int $milliseconds): void
    {
        $this->readLockWaitBetweenAttempts = $milliseconds;
    }

    /**
     * Sets the max number of attempts to acquire the lock for the move to processed operation.
     */
    public function setMoveToProcessedLockAttemptLimit(int $limit): void
    {
        $this->moveToProcessedLockAttemptLimit = $limit;
    }

    /**
     * Sets the time in milliseconds between acquire the lock attempts for the move to processed operation.
     */
    public function setMoveToProcessedLockWaitBetweenAttempts(int $milliseconds): void
    {
        $this->moveToProcessedLockWaitBetweenAttempts = $milliseconds;
    }

    /**
     * @param FileManager              $fileManager
     * @param int                      $operationId
     * @param IncludeAccessorInterface $includeAccessor
     * @param ChunkFile[]              $files
     *
     * @return array [[section name, included item index, error message], ...]
     */
    public function updateIncludedChunkIndex(
        FileManager $fileManager,
        int $operationId,
        IncludeAccessorInterface $includeAccessor,
        array $files
    ): array {
        $errors = [];
        $indexData = $this->loadIndexData($fileManager, $operationId);
        foreach ($files as $file) {
            $fileName = $file->getFileName();
            $fileIndex = $file->getFileIndex();
            $sectionName = $file->getSectionName();
            $items = $this->loadChunkFileData($fileManager, $fileName, $sectionName);
            $firstItemOffset = $file->getFirstRecordOffset();
            $indexData[self::FILES][$fileIndex] = [$fileName, $sectionName, $firstItemOffset];
            foreach ($items as $itemIndex => $item) {
                if (\is_array($item)) {
                    $itemIdentifier = null;
                    try {
                        $itemIdentifier = $includeAccessor->getItemIdentifier($item);
                    } catch (\InvalidArgumentException $e) {
                        $errors[] = [$sectionName, $firstItemOffset + $itemIndex, $e->getMessage()];
                    }
                    if (null !== $itemIdentifier) {
                        [$itemType, $itemId] = $itemIdentifier;
                        $itemKey = $this->itemKeyBuilder->buildItemKey($itemType, $itemId);
                        if (!isset($indexData[self::ITEMS][$itemKey])) {
                            $indexData[self::ITEMS][$itemKey] = [$fileIndex, $itemIndex];
                        } else {
                            $errors[] = [
                                $sectionName,
                                $firstItemOffset + $itemIndex,
                                sprintf(
                                    'The item duplicates the item with the index %s',
                                    $indexData[self::ITEMS][$itemKey][self::ITEM_INDEX]
                                )
                            ];
                        }
                    }
                } else {
                    $errors[] = [$sectionName, $firstItemOffset + $itemIndex, 'The item should be an object'];
                }
            }
        }
        $this->saveIndexData($fileManager, $operationId, $indexData);

        return $errors;
    }

    /**
     * Gets included data for the given primary data and locks the include index if it is required.
     *
     * @param FileManager              $fileManager
     * @param int                      $operationId
     * @param IncludeAccessorInterface $includeAccessor
     * @param array                    $data
     *
     * @return IncludedData|null The included items or NULL if it is not possible to get them now
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getIncludedItems(
        FileManager $fileManager,
        int $operationId,
        IncludeAccessorInterface $includeAccessor,
        array $data
    ): ?IncludedData {
        $relationships = $this->getRelationships($includeAccessor, $data);
        if (!$relationships) {
            return new IncludedData($this->itemKeyBuilder, $includeAccessor, $this->fileLockManager);
        }

        return $this->doWithReadLock(
            $this->fileNameProvider->getIncludeIndexFileName($operationId),
            function () use ($fileManager, $operationId, $includeAccessor, $relationships) {
                $loadedItems = [];
                $processedRelationships = [];
                $indexData = $this->loadIndexData($fileManager, $operationId);
                $processedIndexData = $this->loadProcessedIndexData($fileManager, $operationId);
                $includedItemIndexes = $this->loadIncludedItems(
                    $fileManager,
                    $includeAccessor,
                    $indexData,
                    $relationships,
                    $processedRelationships,
                    $loadedItems
                );

                $chunkFileNames = [];
                $includedItems = [];
                foreach ($includedItemIndexes as $fileIndex => $itemIndexes) {
                    [$fileName, $sectionName, $firstItemOffset] = $indexData[self::FILES][$fileIndex];
                    $chunkFileNames[] = $fileName;
                    foreach ($itemIndexes as $itemIndex) {
                        if (isset($loadedItems[$fileIndex][$itemIndex])) {
                            $item = $loadedItems[$fileIndex][$itemIndex];

                            [$itemType, $itemId] = $includeAccessor->getItemIdentifier($item);
                            $itemKey = $this->itemKeyBuilder->buildItemKey($itemType, $itemId);
                            $includedItems[$itemKey] = [$item, $firstItemOffset + $itemIndex, $sectionName];
                        }
                    }
                }

                $processedItems = [];
                foreach ($processedRelationships as $itemKey => $val) {
                    if (!isset($includedItems[$itemKey]) && isset($processedIndexData[$itemKey])) {
                        $processedItems[$itemKey] = $processedIndexData[$itemKey];
                    }
                }

                if (!$includedItems) {
                    return new IncludedData(
                        $this->itemKeyBuilder,
                        $includeAccessor,
                        $this->fileLockManager,
                        null,
                        [],
                        $processedItems
                    );
                }

                $this->markAsLinked($fileManager, $operationId, array_keys($includedItems));
                $lockFileNames = $this->acquireLockForChunkFiles($chunkFileNames);
                if (null === $lockFileNames) {
                    return null;
                }

                return new IncludedData(
                    $this->itemKeyBuilder,
                    $includeAccessor,
                    $this->fileLockManager,
                    $lockFileNames,
                    $includedItems,
                    $processedItems
                );
            }
        );
    }

    /**
     * Moves the given included items to the collection of processed included items.
     *
     * @param FileManager $fileManager
     * @param int         $operationId
     * @param array       $dataToMove [[type, id, new id], ...]
     */
    public function moveToProcessed(FileManager $fileManager, int $operationId, array $dataToMove): void
    {
        $this->doWithMoveToProcessedLock(
            $this->fileNameProvider->getIncludeIndexFileName($operationId),
            function () use ($fileManager, $operationId, $dataToMove) {
                $indexData = $this->loadIndexData($fileManager, $operationId);
                $processedIndexData = $this->loadProcessedIndexData($fileManager, $operationId);

                $itemsToRemove = [];
                foreach ($dataToMove as [$type, $id, $newId]) {
                    $itemKey = $this->itemKeyBuilder->buildItemKey($type, $id);
                    $processedIndexData[$itemKey] = $newId;
                    $item = $indexData[self::ITEMS][$itemKey] ?? null;
                    if (null !== $item) {
                        unset($indexData[self::ITEMS][$itemKey]);
                        [$fileIndex, $itemIndex] = $item;
                        $itemsToRemove[$fileIndex][] = $itemIndex;
                    }
                }
                foreach ($itemsToRemove as $fileIndex => $itemIndexes) {
                    $file = $indexData[self::FILES][$fileIndex];
                    $fileName = $file[self::FILE_NAME];
                    $sectionName = $file[self::FILE_SECTION_NAME];
                    $fileData = JsonUtil::decode($fileManager->getFileContent($fileName));
                    foreach ($itemIndexes as $itemIndex) {
                        $fileData[$sectionName][$itemIndex] = null;
                    }
                    if ($this->isChunkFileEmpty($fileData)) {
                        $fileManager->deleteFile($fileName);
                        $indexData[self::FILES][$fileIndex][self::FILE_NAME] = '';
                    } else {
                        $fileManager->writeToStorage(JsonUtil::encode($fileData), $fileName);
                    }
                }
                $this->saveProcessedIndexData($fileManager, $operationId, $processedIndexData);
                $this->saveIndexData($fileManager, $operationId, $indexData);
            }
        );
    }

    /**
     * Gets indexes of all included items that were not linked to any primary item.
     *
     * @param FileManager $fileManager
     * @param int         $operationId
     *
     * @return array [section name => [item index, ...], ...]
     */
    public function getNotLinkedIncludedItemIndexes(FileManager $fileManager, int $operationId): array
    {
        $result = [];
        $indexData = $this->loadIndexData($fileManager, $operationId);
        if (!empty($indexData[self::ITEMS])) {
            $linkedIndexData = $this->loadLinkedIndexData($fileManager, $operationId);
            foreach ($indexData[self::ITEMS] as $itemKey => [$fileIndex, $itemIndex]) {
                if (!isset($linkedIndexData[$itemKey])) {
                    $file = $indexData[self::FILES][$fileIndex];
                    $result[$file[self::FILE_SECTION_NAME]][] = $file[self::FILE_FIRST_ITEM_OFFSET] + $itemIndex;
                }
            }
        }

        return $result;
    }

    /**
     * Marks the given included items as linked to at least one primary item.
     *
     * @param FileManager $fileManager
     * @param int         $operationId
     * @param array       $items [item key, ...]
     */
    private function markAsLinked(FileManager $fileManager, int $operationId, array $items): void
    {
        $hasChanges = false;
        $linkedIndexData = $this->loadLinkedIndexData($fileManager, $operationId);
        foreach ($items as $itemKey) {
            if (!isset($linkedIndexData[$itemKey])) {
                $linkedIndexData[$itemKey] = true;
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            $this->saveLinkedIndexData($fileManager, $operationId, $linkedIndexData);
        }
    }

    private function getRelationships(IncludeAccessorInterface $includeAccessor, array $data): array
    {
        $relationships = [];
        foreach ($data as $item) {
            $itemRelationships = $includeAccessor->getRelationships($includeAccessor->getPrimaryItemData($item));
            foreach ($itemRelationships as $itemRelationshipKey => $itemRelationship) {
                if (!isset($relationships[$itemRelationshipKey])) {
                    $relationships[$itemRelationshipKey] = $itemRelationship;
                }
            }
        }

        return $relationships;
    }

    /**
     * @param FileManager              $fileManager
     * @param IncludeAccessorInterface $includeAccessor
     * @param array                    $indexData
     * @param array                    $relationships          [item key => [type, id], ...]
     * @param array                    $processedRelationships [item key => true, ...]
     * @param array                    $loadedItems            [file index => items, ...]
     *
     * @return array [file index => [item index, ...], ...]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadIncludedItems(
        FileManager $fileManager,
        IncludeAccessorInterface $includeAccessor,
        array $indexData,
        array $relationships,
        array &$processedRelationships,
        array &$loadedItems
    ): array {
        foreach ($relationships as $relationshipKey => $relationship) {
            $processedRelationships[$relationshipKey] = true;
        }

        $includedItems = $this->fillIncludedItems($indexData, $relationships);
        $includedRelationships = [];
        foreach ($includedItems as $fileIndex => $itemIndexes) {
            if (!isset($loadedItems[$fileIndex])) {
                $file = $indexData[self::FILES][$fileIndex];
                $loadedItems[$fileIndex] = $this->loadChunkFileData(
                    $fileManager,
                    $file[self::FILE_NAME],
                    $file[self::FILE_SECTION_NAME]
                );
            }
            foreach ($itemIndexes as $itemIndex) {
                if (isset($loadedItems[$fileIndex][$itemIndex])) {
                    $item = $loadedItems[$fileIndex][$itemIndex];
                    $itemRelationships = $includeAccessor->getRelationships($item);
                    foreach ($itemRelationships as $itemRelationshipKey => $itemRelationship) {
                        if (!isset($processedRelationships[$itemRelationshipKey])) {
                            $processedRelationships[$itemRelationshipKey] = true;
                            $includedRelationships[$itemRelationshipKey] = $itemRelationship;
                        }
                    }
                }
            }
        }
        if ($includedRelationships) {
            $newIncludedItems = $this->loadIncludedItems(
                $fileManager,
                $includeAccessor,
                $indexData,
                $includedRelationships,
                $processedRelationships,
                $loadedItems
            );
            foreach ($newIncludedItems as $fileIndex => $itemIndexes) {
                if (isset($includedItems[$fileIndex])) {
                    $includedItems[$fileIndex] = array_merge($includedItems[$fileIndex], $itemIndexes);
                } else {
                    $includedItems[$fileIndex] = $itemIndexes;
                }
            }
        }

        return $includedItems;
    }

    /**
     * @param array $indexData
     * @param array $relationships [item key => [type, id], ...]
     *
     * @return array [file index => [item index, ...], ...]
     */
    private function fillIncludedItems(array $indexData, array $relationships): array
    {
        $includedItems = [];
        foreach ($relationships as $itemKey => $relationship) {
            if (isset($indexData[self::ITEMS][$itemKey])) {
                [$fileIndex, $itemIndex] = $indexData[self::ITEMS][$itemKey];
                $includedItems[$fileIndex][] = $itemIndex;
            }
        }

        return $includedItems;
    }

    /**
     * @param array $fileData [section name => [item, ...], ...]
     *
     * @return bool
     */
    private function isChunkFileEmpty(array $fileData): bool
    {
        foreach ($fileData as $sectionName => $items) {
            foreach ($items as $item) {
                if (null !== $item) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param FileManager $fileManager
     * @param string      $fileName
     * @param string      $sectionName
     *
     * @return array [item, ...]
     */
    private function loadChunkFileData(FileManager $fileManager, string $fileName, string $sectionName): array
    {
        $data = JsonUtil::decode($fileManager->getFileContent($fileName));

        return $data[$sectionName];
    }

    /**
     * @param FileManager $fileManager
     * @param int         $operationId
     *
     * @return array [
     *                  'files' => [file index => [file name, section name, first item offset], ...],
     *                  'items' => [item key => [file index, item index], ...]
     *               ]
     */
    private function loadIndexData(FileManager $fileManager, int $operationId): array
    {
        $file = $fileManager->getFile(
            $this->fileNameProvider->getIncludeIndexFileName($operationId),
            false
        );

        if (null === $file) {
            return [self::FILES => [], self::ITEMS => []];
        }

        return JsonUtil::decode($file->getContent());
    }

    private function saveIndexData(FileManager $fileManager, int $operationId, array $data): void
    {
        $fileManager->writeToStorage(
            JsonUtil::encode($data),
            $this->fileNameProvider->getIncludeIndexFileName($operationId)
        );
    }

    /**
     * @param FileManager $fileManager
     * @param int         $operationId
     *
     * @return array [item key => new id, ...]
     */
    private function loadProcessedIndexData(FileManager $fileManager, int $operationId): array
    {
        $file = $fileManager->getFile(
            $this->fileNameProvider->getProcessedIncludeIndexFileName($operationId),
            false
        );

        return null === $file
            ? []
            : JsonUtil::decode($file->getContent());
    }

    private function saveProcessedIndexData(FileManager $fileManager, int $operationId, array $data): void
    {
        $fileManager->writeToStorage(
            JsonUtil::encode($data),
            $this->fileNameProvider->getProcessedIncludeIndexFileName($operationId)
        );
    }

    /**
     * @param FileManager $fileManager
     * @param int         $operationId
     *
     * @return array [item key => true, ...]
     */
    private function loadLinkedIndexData(FileManager $fileManager, int $operationId): array
    {
        $file = $fileManager->getFile(
            $this->fileNameProvider->getLinkedIncludeIndexFileName($operationId),
            false
        );

        return null === $file
            ? []
            : array_fill_keys(JsonUtil::decode($file->getContent()), true);
    }

    private function saveLinkedIndexData(FileManager $fileManager, int $operationId, array $data): void
    {
        $fileManager->writeToStorage(
            JsonUtil::encode(array_keys($data)),
            $this->fileNameProvider->getLinkedIncludeIndexFileName($operationId)
        );
    }

    private function acquireReadLock(string $fileName): ?string
    {
        $lockFileName = $this->fileNameProvider->getLockFileName($fileName);
        if (!$this->fileLockManager->acquireLock(
            $lockFileName,
            $this->readLockAttemptLimit,
            $this->readLockWaitBetweenAttempts
        )) {
            return null;
        }

        return $lockFileName;
    }

    private function acquireMoveToProcessedLock(string $fileName): ?string
    {
        $lockFileName = $this->fileNameProvider->getLockFileName($fileName);
        if (!$this->fileLockManager->acquireLock(
            $lockFileName,
            $this->moveToProcessedLockAttemptLimit,
            $this->moveToProcessedLockWaitBetweenAttempts
        )) {
            return null;
        }

        return $lockFileName;
    }

    /**
     * @param string[] $chunkFileNames
     *
     * @return string[]|null
     */
    private function acquireLockForChunkFiles(array $chunkFileNames): ?array
    {
        $lockFileNames = [];
        foreach ($chunkFileNames as $fileName) {
            $lockFileName = $this->acquireReadLock($fileName);
            if ($lockFileName) {
                $lockFileNames[] = $lockFileName;
            } else {
                $this->logger->warning(sprintf(
                    'Not possible to get included items now'
                    . ' because the lock cannot be acquired for the "%s" chunk file.',
                    $fileName
                ));
                foreach ($lockFileNames as $lockFileName) {
                    $this->fileLockManager->releaseLock($lockFileName);
                }
                $lockFileNames = null;
                break;
            }
        }

        return $lockFileNames;
    }

    private function doWithReadLock(string $indexFileName, callable $closure): ?IncludedData
    {
        $lockFileName = $this->acquireReadLock($indexFileName);
        if (!$lockFileName) {
            $this->logger->warning(sprintf(
                'Not possible to get included items now because the lock cannot be acquired for the "%s" file.',
                $indexFileName
            ));

            return null;
        }

        try {
            return $closure();
        } finally {
            $this->fileLockManager->releaseLock($lockFileName);
        }
    }

    private function doWithMoveToProcessedLock(string $indexFileName, callable $closure): void
    {
        $lockFileName = $this->acquireMoveToProcessedLock($indexFileName);
        if (!$lockFileName) {
            throw new RuntimeException(sprintf(
                'Not possible to move included items to processed'
                . ' because the lock cannot be acquired for the "%s" file.',
                $indexFileName
            ));
        }

        try {
            $closure();
        } finally {
            $this->fileLockManager->releaseLock($lockFileName);
        }
    }
}
