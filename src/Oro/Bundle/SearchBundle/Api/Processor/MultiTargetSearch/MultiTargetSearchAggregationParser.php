<?php

namespace Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\SearchBundle\Query\Expression\TokenInfo;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * Analyzes the search API resource aggregation expression and converts it to a list of aggregations.
 */
class MultiTargetSearchAggregationParser
{
    public function __construct(
        private readonly array $searchFieldMappings,
        private readonly array $fieldMappings
    ) {
    }

    /**
     * @return array [alias => [[field name, field type, function], ...], ...]
     */
    public function parse(array $aggregates): array
    {
        $parsedAggregates = [];
        foreach ($aggregates as $aggregate) {
            $this->parseAggregate($parsedAggregates, $aggregate);
        }

        return $parsedAggregates;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function parseAggregate(array &$parsedAggregates, string $aggregate): void
    {
        $delimiterCount = substr_count($aggregate, ' ');
        if ($delimiterCount < 1 || $delimiterCount > 2) {
            throw $this->createInvalidAggregateDefinitionException($aggregate);
        }

        $parts = \explode(' ', $aggregate);
        [$field, $function] = $parts;
        if (!$field || !$function || (\array_key_exists(2, $parts) && !$parts[2])) {
            throw $this->createInvalidAggregateDefinitionException($aggregate);
        }
        if (!\in_array($function, TokenInfo::getAggregatingFunctions(), true)) {
            throw new InvalidFilterException(\sprintf(
                'The aggregating function "%s" is not supported.',
                $function
            ));
        }

        $fieldAlias = $parts[2] ?? $field . \ucfirst($function);
        $fieldNames = $this->fieldMappings[$field] ?? null;
        if (!$fieldNames) {
            $fieldNames = [$field];
        } elseif (\is_string($fieldNames)) {
            $fieldNames = [$fieldNames];
        }
        /** @var string $fieldName */
        foreach ($fieldNames as $fieldName) {
            $searchFieldMapping = $this->searchFieldMappings[$fieldName] ?? null;
            $fieldType = $searchFieldMapping ? $searchFieldMapping['type'] : SearchQuery::TYPE_TEXT;
            if (!\in_array($function, TokenInfo::getAggregatingFunctionsForType($fieldType), true)) {
                throw new InvalidFilterException(\sprintf(
                    'The aggregating function "%s" is not supported for the field type "%s".',
                    $function,
                    $fieldType
                ));
            }
            $parsedAggregates[$fieldAlias][] = [$fieldName, $fieldType, $function];
        }
    }

    private function createInvalidAggregateDefinitionException(string $aggregate): InvalidFilterException
    {
        return new InvalidFilterException(\sprintf(
            'The value "%s" must match one of the following patterns:'
            . ' "fieldName functionName" or "fieldName functionName resultName".',
            $aggregate
        ));
    }
}
