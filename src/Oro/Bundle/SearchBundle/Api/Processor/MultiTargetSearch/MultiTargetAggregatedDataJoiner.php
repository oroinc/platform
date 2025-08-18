<?php

namespace Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * Provides a functionality to join aggregated data returned for the search API resource.
 */
class MultiTargetAggregatedDataJoiner
{
    /**
     * @param array $aggregatedData    [alias => scalar or [value => count, ...], ...]
     * @param array $toJoinAggregation [alias => [function => [field type => [to join alias, ...], ...], ...], ...]
     *
     * @return array [alias => scalar or [value => count, ...], ...]
     */
    public function join(array $aggregatedData, array $toJoinAggregation): array
    {
        foreach ($toJoinAggregation as $alias => $toJoinFunctions) {
            foreach ($toJoinFunctions as $function => $toJoinFieldTypes) {
                foreach ($toJoinFieldTypes as $fieldType => $toJoinAliases) {
                    foreach ($toJoinAliases as $toJoinAlias) {
                        if (!\array_key_exists($toJoinAlias, $aggregatedData)) {
                            continue;
                        }
                        if (\array_key_exists($alias, $aggregatedData)) {
                            $aggregatedData[$alias] = $this->joinAggregate(
                                $aggregatedData[$alias],
                                $aggregatedData[$toJoinAlias],
                                $function,
                                $fieldType
                            );
                        } else {
                            $aggregatedData[$alias] = $aggregatedData[$toJoinAlias];
                        }
                        unset($aggregatedData[$toJoinAlias]);
                    }
                }
            }
        }

        return $aggregatedData;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function joinAggregate(
        mixed $resultAggregate,
        mixed $toJoinAggregate,
        string $function,
        string $fieldType
    ): mixed {
        if (SearchQuery::AGGREGATE_FUNCTION_COUNT === $function) {
            return $this->joinCountAggregate($resultAggregate, $toJoinAggregate);
        }
        if (SearchQuery::AGGREGATE_FUNCTION_SUM === $function) {
            return $resultAggregate + $toJoinAggregate;
        }
        if (SearchQuery::AGGREGATE_FUNCTION_AVG === $function) {
            return ($resultAggregate + $toJoinAggregate) / 2;
        }
        if (SearchQuery::AGGREGATE_FUNCTION_MIN === $function) {
            if (SearchQuery::TYPE_DATETIME === $fieldType) {
                return $this->normalizeDateTime($toJoinAggregate) < $this->normalizeDateTime($resultAggregate)
                    ? $toJoinAggregate
                    : $resultAggregate;
            }

            return min($toJoinAggregate, $resultAggregate);
        }
        if (SearchQuery::AGGREGATE_FUNCTION_MAX === $function) {
            if (SearchQuery::TYPE_DATETIME === $fieldType) {
                return $this->normalizeDateTime($toJoinAggregate) > $this->normalizeDateTime($resultAggregate)
                    ? $toJoinAggregate
                    : $resultAggregate;
            }

            return max($toJoinAggregate, $resultAggregate);
        }
        throw new \LogicException(\sprintf('Unknown aggregation function "%s".', $function));
    }

    private function joinCountAggregate(array $resultAggregate, array $toJoinAggregate): array
    {
        foreach ($toJoinAggregate as $toJoinValue => $toJoinCount) {
            if (\array_key_exists($toJoinValue, $resultAggregate)) {
                $resultAggregate[$toJoinValue] += $toJoinCount;
            } else {
                $resultAggregate[$toJoinValue] = $toJoinCount;
            }
        }

        return $resultAggregate;
    }

    private function normalizeDateTime(mixed $value): mixed
    {
        return \is_string($value)
            ? new \DateTime($value, new \DateTimeZone('UTC'))
            : $value;
    }
}
