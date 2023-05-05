<?php

namespace Oro\Bundle\ApiBundle\Batch\IncludeAccessor;

use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The included data accessor for JSON:API requests.
 */
class JsonApiIncludeAccessor implements IncludeAccessorInterface
{
    private ItemKeyBuilder $itemKeyBuilder;

    public function __construct(ItemKeyBuilder $itemKeyBuilder)
    {
        $this->itemKeyBuilder = $itemKeyBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryItemData(array $item): array
    {
        return $item[JsonApiDoc::DATA];
    }

    /**
     * {@inheritDoc}
     */
    public function setPrimaryItemData(array &$item, array $data): void
    {
        $item[JsonApiDoc::DATA] = $data;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getItemIdentifier(array $item): array
    {
        if (!\array_key_exists(JsonApiDoc::TYPE, $item)) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property is required', JsonApiDoc::TYPE));
        }
        if (!\array_key_exists(JsonApiDoc::ID, $item)) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property is required', JsonApiDoc::ID));
        }

        $type = $item[JsonApiDoc::TYPE];
        if (null === $type) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property should not be null', JsonApiDoc::TYPE));
        }
        if (!\is_string($type)) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property should be a string', JsonApiDoc::TYPE));
        }
        if ('' === $type) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property should not be blank', JsonApiDoc::TYPE));
        }

        $id = $item[JsonApiDoc::ID];
        if (null === $id) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property should not be null', JsonApiDoc::ID));
        }
        if (!\is_string($id)) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property should be a string', JsonApiDoc::ID));
        }
        if ('' === $id) {
            throw new \InvalidArgumentException(sprintf('The \'%s\' property should not be blank', JsonApiDoc::ID));
        }

        return [$type, $id];
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getRelationships(array $item): array
    {
        $result = [];
        if (empty($item[JsonApiDoc::RELATIONSHIPS])) {
            return $result;
        }
        $relationships = $item[JsonApiDoc::RELATIONSHIPS];
        if (!\is_array($relationships)) {
            return $result;
        }

        foreach ($relationships as $relationship) {
            if (empty($relationship[JsonApiDoc::DATA])) {
                continue;
            }
            $relationshipData = $relationship[JsonApiDoc::DATA];
            if (!\is_array($relationships)) {
                continue;
            }
            if (ArrayUtil::isAssoc($relationshipData)) {
                $itemIdentifier = $this->tryGetItemIdentifier($relationshipData);
                if (null !== $itemIdentifier) {
                    [$type, $id] = $itemIdentifier;
                    $itemKey = $this->itemKeyBuilder->buildItemKey($type, $id);
                    if (!isset($result[$itemKey])) {
                        $result[$itemKey] = [$type, $id];
                    }
                }
            } else {
                foreach ($relationshipData as $relationshipItemData) {
                    if (!\is_array($relationshipItemData)) {
                        continue;
                    }
                    $itemIdentifier = $this->tryGetItemIdentifier($relationshipItemData);
                    if (null !== $itemIdentifier) {
                        [$type, $id] = $itemIdentifier;
                        $itemKey = $this->itemKeyBuilder->buildItemKey($type, $id);
                        if (!isset($result[$itemKey])) {
                            $result[$itemKey] = [$type, $id];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function updateRelationships(array &$item, callable $callback): void
    {
        if (empty($item[JsonApiDoc::RELATIONSHIPS])) {
            return;
        }
        $relationships = $item[JsonApiDoc::RELATIONSHIPS];
        if (!\is_array($relationships)) {
            return;
        }

        foreach ($relationships as $key => $relationship) {
            if (empty($relationship[JsonApiDoc::DATA])) {
                continue;
            }
            $relationshipData = $relationship[JsonApiDoc::DATA];
            if (!\is_array($relationships)) {
                continue;
            }
            if (ArrayUtil::isAssoc($relationshipData)) {
                $itemIdentifier = $this->tryGetItemIdentifier($relationshipData);
                if (null !== $itemIdentifier) {
                    [$type, $id] = $itemIdentifier;
                    $newId = $callback($type, $id);
                    if (null !== $newId) {
                        $item[JsonApiDoc::RELATIONSHIPS][$key][JsonApiDoc::DATA][JsonApiDoc::ID] = (string)$newId;
                    }
                }
            } else {
                foreach ($relationshipData as $index => $relationshipItemData) {
                    if (!\is_array($relationshipItemData)) {
                        continue;
                    }
                    $itemIdentifier = $this->tryGetItemIdentifier($relationshipItemData);
                    if (null !== $itemIdentifier) {
                        [$type, $id] = $itemIdentifier;
                        $newId = $callback($type, $id);
                        if (null !== $newId) {
                            $item[JsonApiDoc::RELATIONSHIPS][$key][JsonApiDoc::DATA][$index][JsonApiDoc::ID] =
                                (string)$newId;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $item
     *
     * @return array|null [type, id] or NULL if the item identifier cannot be retrieved
     */
    private function tryGetItemIdentifier(array $item): ?array
    {
        if (!isset($item[JsonApiDoc::TYPE])) {
            return null;
        }
        if (!isset($item[JsonApiDoc::ID])) {
            return null;
        }

        $type = $item[JsonApiDoc::TYPE];
        if (!\is_string($type) || '' === $type) {
            return null;
        }
        $id = $item[JsonApiDoc::ID];
        if (!\is_string($id) || '' === $id) {
            return null;
        }

        return [$type, $id];
    }
}
