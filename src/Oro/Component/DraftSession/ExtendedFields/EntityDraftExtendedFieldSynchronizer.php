<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\ExtendedFields;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;

/**
 * Synchronizes the value of a single extended field between two entity instances,
 * handling scalar values, single relations, and collections.
 */
class EntityDraftExtendedFieldSynchronizer
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly FieldHelper $fieldHelper,
    ) {
    }

    public function synchronize(object $source, object $target, string $fieldName, string $fieldType): void
    {
        if ($this->isCollectionFieldType($fieldType)) {
            $this->syncCollectionField($source, $target, $fieldName);
        } elseif ($this->isSingleRelationFieldType($fieldType)) {
            $sourceValue = $this->fieldHelper->getObjectValue($source, $fieldName);
            $this->fieldHelper->setObjectValue(
                $target,
                $fieldName,
                $this->draftSyncReferenceResolver->getReference($sourceValue),
            );
        } else {
            $sourceValue = $this->fieldHelper->getObjectValue($source, $fieldName);
            if (is_object($sourceValue)) {
                $sourceValue = clone $sourceValue;
            }

            $this->fieldHelper->setObjectValue($target, $fieldName, $sourceValue);
        }
    }

    private function isCollectionFieldType(string $fieldType): bool
    {
        return ExtendHelper::isMultiEnumType($fieldType)
            || in_array($fieldType, RelationType::$toManyRelations, true);
    }

    private function isSingleRelationFieldType(string $fieldType): bool
    {
        return ExtendHelper::isSingleEnumType($fieldType)
            || in_array($fieldType, RelationType::$toOneRelations, true);
    }

    private function syncCollectionField(object $source, object $target, string $fieldName): void
    {
        $sourceCollection = $this->fieldHelper->getObjectValue($source, $fieldName);
        $targetCollection = $this->fieldHelper->getObjectValue($target, $fieldName);

        if (!$sourceCollection instanceof Collection || !$targetCollection instanceof Collection) {
            return;
        }

        $targetCollection->clear();

        foreach ($sourceCollection as $item) {
            $reference = $this->draftSyncReferenceResolver->getReference($item);
            if (null !== $reference) {
                $targetCollection->add($reference);
            }
        }
    }
}
