<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Handles Associations logic for Extend Entity
 */
class AssociationExtendEntity
{
    private static function getAssociations(object $object, AbstractAssociationEntityFieldExtension $assocExt): array
    {
        $entity = ExtendedEntityFieldsProcessor::getEntityMetadata($object);
        if (null === $entity) {
            return [];
        }
        $type = $assocExt->getRelationType();
        $kind = $assocExt->getRelationKind();

        $associations = [];
        $entityRelations = $entity->get('relation');
        if (null === $entityRelations) {
            return $associations;
        }
        foreach ($entityRelations as $relation) {
            $fieldConfigId = $relation['field_id'];
            $isSupported   = $fieldConfigId instanceof FieldConfigId
                && ($fieldConfigId->getFieldType() === $type
                    || ($type === RelationType::MULTIPLE_MANY_TO_ONE
                        && RelationType::MANY_TO_ONE === $fieldConfigId->getFieldType()))
                && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                    $relation['target_entity'],
                    $kind
                );
            if ($isSupported) {
                $associations[$relation['target_entity']] = $fieldConfigId->getFieldName();
            }
        }

        return $associations;
    }

    public static function support(
        object $object,
        string $targetClass,
        AbstractAssociationEntityFieldExtension $assocExt
    ): bool {
        $associations = static::getAssociations($object, $assocExt);
        $targetClass  = CachedClassUtils::getRealClass($targetClass);

        return isset($associations[$targetClass]);
    }

    public static function getTargets(
        object $object,
        AbstractAssociationEntityFieldExtension $assocExt,
        ?string $targetClass = null
    ): array|object {
        $associations = static::getAssociations($object, $assocExt);
        if ($targetClass === null) {
            $targets = [];
            foreach ($associations as $fieldName) {
                switch ($assocExt->getRelationType()) {
                    case RelationType::MANY_TO_MANY:
                        $collection = $object->get($fieldName);
                        if ($collection instanceof Collection && $collection->count() > 0) {
                            $targets = array_merge($targets, $collection->toArray());
                        }
                        break;
                    case RelationType::MULTIPLE_MANY_TO_ONE:
                        $target = $object->get($fieldName);
                        if (null !== $target) {
                            $targets[] = $target;
                        }
                        break;
                }
            }

            return $targets;
        }

        $targetClass = CachedClassUtils::getRealClass($targetClass);
        if (isset($associations[$targetClass])) {
            return $object->get($associations[$targetClass]);
        }

        throw new \RuntimeException(sprintf('The association with "%s" entity was not configured.', $targetClass));
    }

    public static function getTarget(object $object, AbstractAssociationEntityFieldExtension $assocExt): ?object
    {
        $associations = static::getAssociations($object, $assocExt);
        foreach ($associations as $fieldName) {
            $target = $object->get($fieldName);
            if (null !== $target) {
                return $target;
            }
        }

        return null;
    }

    protected static function resetTargets(object $object, AbstractAssociationEntityFieldExtension $assocExt): void
    {
        $associations = static::getAssociations($object, $assocExt);
        foreach ($associations as $fieldName) {
            $object->set($fieldName, null);
        }
    }

    public static function setTarget(
        object $object,
        AbstractAssociationEntityFieldExtension $assocExt,
        ?object $target = null
    ): void {
        if ($target === null) {
            static::resetTargets($object, $assocExt);
            return;
        }

        $targetClass = CachedClassUtils::getClass($target);
        $associations = static::getAssociations($object, $assocExt);
        foreach ($associations as $className => $fieldName) {
            if ($className === $targetClass) {
                static::resetTargets($object, $assocExt);
                $object->set($fieldName, $target);
                return;
            }
        }

        throw new \RuntimeException(
            sprintf('The association with "%s" entity was not configured.', $targetClass)
        );
    }

    public static function hasTarget(
        object $object,
        object $target,
        AbstractAssociationEntityFieldExtension $assocExt
    ): bool {
        $associations = static::getAssociations($object, $assocExt);
        $targetClass  = CachedClassUtils::getClass($target);

        if (isset($associations[$targetClass])) {
            switch ($assocExt->getRelationType()) {
                case RelationType::MANY_TO_MANY:
                    $collection = $object->get($associations[$targetClass]);
                    return $collection instanceof Collection && $collection->contains($target);
                case RelationType::MULTIPLE_MANY_TO_ONE:
                    return $target === $object->get($associations[$targetClass]);
            }
        }

        return false;
    }

    public static function addTarget(
        object $object,
        object $target,
        AbstractAssociationEntityFieldExtension $assocExt
    ): void {
        $associations = static::getAssociations($object, $assocExt);
        $targetClass  = CachedClassUtils::getClass($target);

        if (isset($associations[$targetClass])) {
            switch ($assocExt->getRelationType()) {
                case RelationType::MANY_TO_MANY:
                    $collection = $object->get($associations[$targetClass]);
                    if ($collection instanceof Collection && !$collection->contains($target)) {
                        $collection->add($target);
                    }
                    return;
                case RelationType::MULTIPLE_MANY_TO_ONE:
                    $object->set($associations[$targetClass], $target);
                    return;
            }
        }

        throw new \RuntimeException(
            sprintf('The association with "%s" entity was not configured.', $targetClass)
        );
    }

    public static function removeTarget(
        object $object,
        object $target,
        AbstractAssociationEntityFieldExtension $assocExt
    ): void {
        $associations = static::getAssociations($object, $assocExt);
        $targetClass  = CachedClassUtils::getClass($target);

        if (isset($associations[$targetClass])) {
            switch ($assocExt->getRelationType()) {
                case RelationType::MANY_TO_MANY:
                    $collection = $object->get($associations[$targetClass]);
                    if ($collection instanceof Collection && $collection->contains($target)) {
                        $collection->removeElement($target);
                    }
                    return;
                case RelationType::MULTIPLE_MANY_TO_ONE:
                    $object->set($associations[$targetClass], null);
                    return;
            }
        }

        throw new \RuntimeException(
            sprintf('The association with "%s" entity was not configured.', $targetClass)
        );
    }
}
