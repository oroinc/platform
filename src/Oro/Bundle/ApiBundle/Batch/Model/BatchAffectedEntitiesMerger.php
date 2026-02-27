<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

/**
 * This class provides a way to merge entities affected by a batch operation.
 */
final class BatchAffectedEntitiesMerger
{
    public static function mergeAffectedEntities(array &$affectedEntities, array $toMerge): void
    {
        self::mergeEntities($affectedEntities, $toMerge, 'primary');
        self::mergeEntities($affectedEntities, $toMerge, 'included');
        self::mergePayload($affectedEntities, $toMerge, 'payload');
    }

    public static function mergePayloadValue(mixed $value, mixed $toMergeValue): mixed
    {
        return self::mergeValues($value, $toMergeValue);
    }

    private static function mergeEntities(array &$affectedEntities, array $toMerge, string $sectionName): void
    {
        if (isset($toMerge[$sectionName])) {
            if (isset($affectedEntities[$sectionName])) {
                foreach ($toMerge[$sectionName] as $item) {
                    $affectedEntities[$sectionName][] = $item;
                }
            } else {
                $affectedEntities[$sectionName] = $toMerge[$sectionName];
            }
        }
    }

    private static function mergePayload(array &$affectedEntities, array $toMerge, string $sectionName): void
    {
        if (isset($toMerge[$sectionName])) {
            $affectedEntities[$sectionName] = isset($affectedEntities[$sectionName])
                ? self::mergeValues($affectedEntities[$sectionName], $toMerge[$sectionName])
                : $toMerge[$sectionName];
        }
    }

    private static function mergeValues(mixed $value, mixed $toMergeValue): mixed
    {
        if (\is_array($value) && \is_array($toMergeValue)) {
            foreach ($toMergeValue as $k => $v) {
                if (\is_int($k)) {
                    $value[] = $v;
                } elseif (\array_key_exists($k, $value)) {
                    $value[$k] = self::mergeValues($value[$k], $v);
                } else {
                    $value[$k] = $v;
                }
            }

            return $value;
        }

        return $toMergeValue;
    }
}
