<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\DataTypeDescribeHelper;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;

/**
 * Represents a storage for OpenAPI parameters.
 */
class ParameterStorage
{
    use ItemStorageTrait;
    use ItemKeyBuildTrait;

    private const COLLECTION_NAME = 'parameters';

    private DataTypeDescribeHelper $dataTypeDescribeHelper;

    public function __construct(DataTypeDescribeHelper $dataTypeDescribeHelper)
    {
        $this->dataTypeDescribeHelper = $dataTypeDescribeHelper;
    }

    public function registerParameter(
        OA\OpenApi $api,
        string $parameterClass,
        string $name,
        array $parameter
    ): OA\Parameter {
        $this->ensureComponentCollectionInitialized($api, self::COLLECTION_NAME);

        $itemKey = $this->getParameterKey($parameterClass, $name, $parameter);
        $existingItem = $this->findItem($api, $itemKey, self::COLLECTION_NAME);
        if (null !== $existingItem) {
            return $existingItem;
        }

        $suggestedItemName = $this->getSuggestedParameterName($name);
        $itemName = $this->resolveItemName($suggestedItemName);
        $item = $this->createItem($api, $itemName, $parameterClass, $name, $parameter);
        $this->saveItem($api, $item, $itemKey, $itemName, $suggestedItemName, self::COLLECTION_NAME);

        return $item;
    }

    private function getParameterKey(string $parameterClass, string $name, array $parameter): string
    {
        $parameter['class'] = $parameterClass;
        $parameter['name'] = $name;

        return $this->getItemKey($parameter);
    }

    private function getSuggestedParameterName(string $name): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-', '.', '[', ']'], ' ', $name))));
    }

    private function createItem(
        OA\OpenApi $api,
        string $itemName,
        string $parameterClass,
        string $name,
        array $parameter
    ): OA\Parameter {
        /** @var OA\Parameter $item */
        $item = Util::createChildItem($parameterClass, $api->components, $name);
        $item->parameter = $itemName;
        $item->name = $name;
        $description = $parameter['description'] ?? null;
        if ($description) {
            $item->description = $description;
        }
        $example = $parameter['example'] ?? null;
        if ($example) {
            $item->example = $example;
        }
        $this->dataTypeDescribeHelper->registerParameterType(
            $api,
            $item,
            $parameter['type'] ?? null,
            $parameter['requirement'] ?? null,
            $parameter['default'] ?? null
        );

        return $item;
    }
}
