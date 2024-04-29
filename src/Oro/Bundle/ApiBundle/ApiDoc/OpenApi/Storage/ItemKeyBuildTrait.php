<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by storages where collisions between different items with the same hash possible.
 */
trait ItemKeyBuildTrait
{
    /** @var array [item hash => [[item hash suffix, serialized item], ...], ...] */
    private array $resolveDuplicateData = [];

    private function getItemKey(array $item): string
    {
        $serializedItem = serialize($item);
        $itemHash = md5($serializedItem);

        // fix collisions when different items have the same hash
        $resolveDuplicateDataItem = $this->resolveDuplicateData[$itemHash] ?? null;
        if (null === $resolveDuplicateDataItem) {
            $this->resolveDuplicateData[$itemHash] = [['', $serializedItem]];
        } else {
            $existingItemHash = null;
            foreach ($resolveDuplicateDataItem as [$existingHashSuffix, $existingSerializedItem]) {
                if ($existingSerializedItem === $serializedItem) {
                    $existingItemHash = $itemHash . $existingHashSuffix;
                    break;
                }
            }
            if (null === $existingItemHash) {
                $itemHashSuffix = '_' . \count($this->resolveDuplicateData);
                $this->resolveDuplicateData[$itemHash][] = [$itemHashSuffix, $serializedItem];
                $itemHash .= $itemHashSuffix;
            } else {
                $itemHash = $existingItemHash;
            }
        }

        return $itemHash;
    }
}
