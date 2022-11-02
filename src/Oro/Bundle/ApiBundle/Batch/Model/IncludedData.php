<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\IncludeAccessorInterface;
use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;

/**
 * Represents a subset of additional entities included into Batch API request used by a specific batch operation.
 */
class IncludedData
{
    private const ITEM_DATA = 0;
    private const ITEM_INCLUDED_INDEX = 1;
    private const ITEM_SECTION_NAME = 2;

    private ItemKeyBuilder $itemKeyBuilder;
    private IncludeAccessorInterface $includeAccessor;
    private FileLockManager $fileLockManager;

    /** @var string[]|null */
    private ?array $lockFileNames;
    /** @var array [item key => [item, included item index, section name], ...] */
    private array $items;
    /** @var array [item key => new id, ...] */
    private array $processedItems;
    /** @var array|null [included item index => item index, ...] */
    private ?array $includedIndexMap = null;
    /** @var string[]|null */
    private ?array $sectionNames = null;

    public function __construct(
        ItemKeyBuilder $itemKeyBuilder,
        IncludeAccessorInterface $includeAccessor,
        FileLockManager $fileLockManager,
        array $lockFileNames = null,
        array $items = [],
        array $processedItems = []
    ) {
        $this->itemKeyBuilder = $itemKeyBuilder;
        $this->includeAccessor = $includeAccessor;
        $this->fileLockManager = $fileLockManager;
        $this->lockFileNames = $lockFileNames;
        $this->items = $items;
        $this->processedItems = $processedItems;
    }

    /**
     * Gets an accessor for the included data.
     */
    public function getIncludeAccessor(): IncludeAccessorInterface
    {
        return $this->includeAccessor;
    }

    /**
     * Gets an included item by its index in source data.
     */
    public function getIncludedItem(int $includedItemIndex): ?array
    {
        if (null === $this->includedIndexMap) {
            $this->includedIndexMap = [];
            foreach ($this->items as $itemKey => $item) {
                $this->includedIndexMap[$item[self::ITEM_INCLUDED_INDEX]] = $itemKey;
            }
        }

        $itemKey = $this->includedIndexMap[$includedItemIndex] ?? null;

        return null === $itemKey ? null : $this->items[$itemKey][self::ITEM_DATA];
    }

    /**
     * Gets the index for the included item in source data.
     */
    public function getIncludedItemIndex(string $itemType, string $itemId): ?int
    {
        $item = $this->items[$this->itemKeyBuilder->buildItemKey($itemType, $itemId)] ?? null;

        return null === $item ? null : $item[self::ITEM_INCLUDED_INDEX];
    }

    /**
     * Gets the section name for the included item in source data.
     */
    public function getIncludedItemSectionName(string $itemType, string $itemId): ?string
    {
        $item = $this->items[$this->itemKeyBuilder->buildItemKey($itemType, $itemId)] ?? null;

        return null === $item ? null : $item[self::ITEM_SECTION_NAME];
    }

    /**
     * Gets the section names for all included items in source data.
     *
     * @return string[]
     */
    public function getAllSectionNames(): array
    {
        if (null === $this->sectionNames) {
            $sectionNames = [];
            foreach ($this->items as $item) {
                $sectionName = $item[self::ITEM_SECTION_NAME];
                if (!\array_key_exists($sectionName, $sectionNames)) {
                    $sectionNames[$sectionName] = true;
                }
            }
            $this->sectionNames = array_keys($sectionNames);
        }

        return $this->sectionNames;
    }

    /**
     * Checks whether there is at least one already processed included item.
     */
    public function hasProcessedIncludedItems(): bool
    {
        return !empty($this->processedItems);
    }

    /**
     * Gets the identifier of already processed included item.
     */
    public function getProcessedIncludedItemId(string $itemType, string $itemId): mixed
    {
        return $this->processedItems[$this->itemKeyBuilder->buildItemKey($itemType, $itemId)] ?? null;
    }

    /**
     * Unlocks chunk files with included data to allow to use them by other batch operations.
     */
    public function unlock(): void
    {
        if (null !== $this->lockFileNames) {
            foreach ($this->lockFileNames as $lockFileName) {
                $this->fileLockManager->releaseLock($lockFileName);
            }
            $this->lockFileNames = null;
        }
    }
}
