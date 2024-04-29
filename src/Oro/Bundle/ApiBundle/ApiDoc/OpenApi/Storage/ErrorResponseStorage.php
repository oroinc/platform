<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;

/**
 * Represents a storage for OpenAPI error responses.
 */
class ErrorResponseStorage
{
    use ItemStorageTrait;
    use ItemKeyBuildTrait;

    private const COLLECTION_NAME = 'responses';

    public function registerErrorResponse(
        OA\OpenApi $api,
        int $statusCode,
        string $description,
        string $mediaType,
        string $schema,
        ?array $headers = null
    ): OA\Response {
        $this->ensureComponentCollectionInitialized($api, self::COLLECTION_NAME);

        $itemKey = $this->getItemKey([$statusCode, $description, $mediaType, $schema, $headers]);
        $existingItem = $this->findItem($api, $itemKey, self::COLLECTION_NAME);
        if (null !== $existingItem) {
            return $existingItem;
        }

        $suggestedItemName = 'err' . $statusCode;
        $itemName = $this->resolveItemName($suggestedItemName);
        $item = $this->createItem($api, $itemName, $description, $mediaType, $schema, $headers);
        $this->saveItem($api, $item, $itemKey, $itemName, $suggestedItemName, self::COLLECTION_NAME);

        return $item;
    }

    private function resolveItemName(string $suggestedItemName): string
    {
        $itemName = $suggestedItemName . '_1';
        // resolve the item name when there is another item with the suggested name
        $existingItemKeys = $this->itemKeys[$suggestedItemName] ?? null;
        if (null !== $existingItemKeys) {
            $itemName = $suggestedItemName . '_' . (\count($existingItemKeys) + 1);
        }

        return $itemName;
    }

    private function createItem(
        OA\OpenApi $api,
        string $itemName,
        string $description,
        string $mediaType,
        string $schema,
        ?array $headers
    ): OA\Response {
        $item = Util::createChildItem(OA\Response::class, $api->components);
        $item->response = $itemName;
        $item->description = $description;
        $content = Util::createChildItem(OA\MediaType::class, $item);
        $content->mediaType = $mediaType;
        $content->schema = Util::createSchemaRef($content, $schema);
        $item->content = [$content];
        if ($headers) {
            $headerRefs = [];
            foreach ($headers as $name => $refName) {
                $headerRefs[] = Util::createHeaderRef($item, $name, $refName);
            }
            $item->headers = $headerRefs;
        }

        return $item;
    }
}
