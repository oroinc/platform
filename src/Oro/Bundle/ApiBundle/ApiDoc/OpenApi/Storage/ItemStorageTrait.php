<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;

/**
 * This trait can be used to simplify implementation of different item storages.
 */
trait ItemStorageTrait
{
    /** @var array [item name => item index, ...] */
    private array $itemMap = [];
    /** @var array [item key => item name, ...] */
    private array $itemNames = [];
    /** @var array [suggested item name => [item key => item name, ...], ...] */
    private array $itemKeys = [];

    public function ensureComponentCollectionInitialized(OA\OpenApi $api, string $collectionName): void
    {
        if (!$this->itemMap) {
            Util::ensureComponentCollectionInitialized($api, $collectionName);
        }
    }

    private function findItem(OA\OpenApi $api, string $itemKey, string $collectionName): ?object
    {
        $existingItemName = $this->itemNames[$itemKey] ?? null;
        if (null === $existingItemName) {
            return null;
        }

        return $api->components->{$collectionName}[$this->itemMap[$existingItemName]];
    }

    private function resolveItemName(string $suggestedItemName): string
    {
        $itemName = $suggestedItemName;
        // resolve the item name when there is another item with the suggested name
        $existingItemKeys = $this->itemKeys[$suggestedItemName] ?? null;
        if (null !== $existingItemKeys) {
            $itemName = $suggestedItemName . \count($existingItemKeys);
        }

        return $itemName;
    }

    private function saveItem(
        OA\OpenApi $api,
        object $item,
        string $itemKey,
        string $itemName,
        string $suggestedItemName,
        string $collectionName
    ): void {
        $this->itemNames[$itemKey] = $itemName;
        $this->itemKeys[$suggestedItemName][$itemKey] = $itemName;
        $index = \count($api->components->{$collectionName});
        $this->itemMap[$itemName] = $index;
        $api->components->{$collectionName}[$index] = $item;
    }
}
