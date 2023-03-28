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
    private static function getRelationType(object $object): string
    {
        return $object->getAssociationRelationType();
    }

    private static function getRelationKind(object $object): ?string
    {
        return $object->getAssociationRelationKind();
    }

    private static function getAssociations(object $object): array
    {
        $entity = ExtendedEntityFieldsProcessor::getEntityMetadata($object);
        if (null === $entity) {
            return [];
        }
        $type = static::getRelationType($object);
        $kind = static::getRelationKind($object);

        $associations = [];
        foreach ($entity->get('relation') as $relation) {
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

    public static function support(object $object, string $targetClass): bool
    {
        $associations = static::getAssociations($object);
        $targetClass  = CachedClassUtils::getRealClass($targetClass);

        return isset($associations[$targetClass]);
    }

    public static function getTargets(object $object, string $targetClass = null): array|object
    {
        $associations = static::getAssociations($object);
        if ($targetClass === null) {
            $targets = [];
            foreach ($associations as $fieldName) {
                switch (static::getRelationType($object)) {
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

    public static function getTarget(object $object): ?object
    {
        $associations = static::getAssociations($object);
        foreach ($associations as $fieldName) {
            $target = $object->get($fieldName);
            if (null !== $target) {
                return $target;
            }
        }

        return null;
    }

    protected static function resetTargets(object $object): void
    {
        $associations = static::getAssociations($object);
        foreach ($associations as $fieldName) {
            $object->set($fieldName, null);
        }
    }

    public static function setTarget(object $object, object $target = null): void
    {
        if ($target === null) {
            static::resetTargets($object);
            return;
        }

        $targetClass = CachedClassUtils::getClass($target);
        $associations = static::getAssociations($object);
        foreach ($associations as $className => $fieldName) {
            if ($className === $targetClass) {
                static::resetTargets($object);
                $object->set($fieldName, $target);
                return;
            }
        }

        throw new \RuntimeException(sprintf('The association with "%s" entity was not configured.', $targetClass));
    }

    public static function hasTarget(object $object, object $target): bool
    {
        $associations = static::getAssociations($object);
        $targetClass  = CachedClassUtils::getClass($target);

        if (isset($associations[$targetClass])) {
            switch (static::getRelationType($object)) {
                case RelationType::MANY_TO_MANY:
                    $collection = $object->get($associations[$targetClass]);
                    return $collection instanceof Collection && $collection->contains($target);
                case RelationType::MULTIPLE_MANY_TO_ONE:
                    return $target === $object->get($associations[$targetClass]);
            }
        }

        return false;
    }

    public static function addTarget(object $object, object $target): void
    {
        $associations = static::getAssociations($object);
        $targetClass  = CachedClassUtils::getClass($target);

        if (isset($associations[$targetClass])) {
            switch (static::getRelationType($object)) {
                case RelationType::MANY_TO_MANY:
                    $collection = $object->get($associations[$targetClass]);
                    if ($collection instanceof Collection && !$collection->contains($target)) {
                        $collection->add($target);
                    }
                    return;
                case RelationType::MULTIPLE_MANY_TO_ONE:
                    $target = $object->set($associations[$targetClass], $target);
                    return;
            }
        }

        throw new \RuntimeException(sprintf('The association with "%s" entity was not configured.', $targetClass));
    }

    public static function removeTarget(object $object, object $target): void
    {
        $associations = static::getAssociations($object);
        $targetClass  = CachedClassUtils::getClass($target);

        if (isset($associations[$targetClass])) {
            switch (static::getRelationType($object)) {
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

        throw new \RuntimeException(sprintf('The association with "%s" entity was not configured.', $targetClass));
    }
}
