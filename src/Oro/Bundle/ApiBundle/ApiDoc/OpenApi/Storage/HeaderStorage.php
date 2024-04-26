<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\DataTypeDescribeHelper;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;

/**
 * Represents a storage for OpenAPI headers.
 */
class HeaderStorage
{
    use ItemStorageTrait;
    use ItemKeyBuildTrait;

    private const COLLECTION_NAME = 'headers';

    private DataTypeDescribeHelper $dataTypeDescribeHelper;

    public function __construct(DataTypeDescribeHelper $dataTypeDescribeHelper)
    {
        $this->dataTypeDescribeHelper = $dataTypeDescribeHelper;
    }

    public function registerHeader(OA\OpenApi $api, string $name, array $header): OA\Header
    {
        $this->ensureComponentCollectionInitialized($api, self::COLLECTION_NAME);

        $itemKey = $this->getHeaderKey($name, $header);
        $existingItem = $this->findItem($api, $itemKey, self::COLLECTION_NAME);
        if (null !== $existingItem) {
            return $existingItem;
        }

        $suggestedItemName = $this->getSuggestedHeaderName($name);
        $itemName = $this->resolveItemName($suggestedItemName);
        $item = $this->createItem($api, $itemName, $header);
        $this->saveItem($api, $item, $itemKey, $itemName, $suggestedItemName, self::COLLECTION_NAME);

        return $item;
    }

    private function getHeaderKey(string $name, array $header): string
    {
        $header['name'] = $name;

        return $this->getItemKey($header);
    }

    private function getSuggestedHeaderName(string $name): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-', '.', '[', ']'], ' ', $name))));
    }

    private function createItem(OA\OpenApi $api, string $itemName, array $header): OA\Header
    {
        $item = Util::createChildItem(OA\Header::class, $api->components);
        $item->header = $itemName;
        $description = $header['description'] ?? null;
        if ($description) {
            $item->description = $description;
        }
        $this->dataTypeDescribeHelper->registerHeaderType(
            $api,
            $item,
            $header['type'] ?? null,
            $header['requirement'] ?? null
        );

        return $item;
    }
}
