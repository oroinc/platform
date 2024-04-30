<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;

/**
 * Represents a storage for OpenAPI request bodies.
 */
class RequestBodyStorage
{
    use ItemStorageTrait;
    use ItemKeyBuildTrait;

    private const COLLECTION_NAME = 'requestBodies';

    public function registerRequestBody(OA\OpenApi $api, string $mediaType, string $schema): OA\RequestBody
    {
        $this->ensureComponentCollectionInitialized($api, self::COLLECTION_NAME);

        $itemKey = $this->getItemKey([$mediaType, $schema]);
        $existingItem = $this->findItem($api, $itemKey, self::COLLECTION_NAME);
        if (null !== $existingItem) {
            return $existingItem;
        }

        $suggestedItemName = $schema;
        $itemName = $this->resolveItemName($suggestedItemName);
        $item = $this->createItem($api, $itemName, $mediaType, $schema);
        $this->saveItem($api, $item, $itemKey, $itemName, $suggestedItemName, self::COLLECTION_NAME);

        return $item;
    }

    private function createItem(OA\OpenApi $api, string $itemName, string $mediaType, string $schema): OA\RequestBody
    {
        $item = Util::createChildItem(OA\RequestBody::class, $api->components);
        $item->request = $itemName;
        $content = Util::createChildItem(OA\MediaType::class, $item);
        $content->mediaType = $mediaType;
        $content->schema = Util::createSchemaRef($content, $schema);
        $item->content = [$content];

        return $item;
    }
}
