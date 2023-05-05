<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\ApiBundle\Filter\FieldFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\SearchBundle\Query\Expression\TokenInfo;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * A filter that can be used to requests aggregated data from a search index.
 */
class SearchAggregationFilter extends StandaloneFilter implements FieldFilterInterface
{
    private SearchFieldResolverFactory $searchFieldResolverFactory;
    private string $entityClass;
    /** @var array [field name => field name in search index, ...] */
    private array $fieldMappings = [];
    /** @var string[] */
    private array $aggregations;

    public function setSearchFieldResolverFactory(SearchFieldResolverFactory $searchFieldResolverFactory): void
    {
        $this->searchFieldResolverFactory = $searchFieldResolverFactory;
    }

    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @param array $fieldMappings [field name => field name in search index, ...]
     */
    public function setFieldMappings(array $fieldMappings): void
    {
        $this->fieldMappings = $fieldMappings;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        if (!$this->entityClass) {
            throw new \InvalidArgumentException('The entity class must not be empty.');
        }

        $this->aggregations = [];
        if (null !== $value) {
            $fieldResolver = $this->searchFieldResolverFactory->createFieldResolver(
                $this->entityClass,
                $this->fieldMappings
            );
            $aggregates = (array)$value->getValue();
            foreach ($aggregates as $aggregate) {
                $this->aggregations[] = $this->parseAggregate($aggregate, $fieldResolver);
            }
        }
    }

    public function applyToSearchQuery(SearchQueryInterface $query): void
    {
        foreach ($this->aggregations as [$fieldName, $fieldType, $function, $alias]) {
            $query->addAggregate($alias, $fieldType . '.' . $fieldName, $function);
        }
    }

    /**
     * @return array [aggregation result field name => search data type, ...]
     */
    public function getAggregationDataTypes(): array
    {
        $result = [];
        foreach ($this->aggregations as [$fieldName, $fieldType, $function, $alias]) {
            $result[$alias] = $fieldType;
        }

        return $result;
    }

    /**
     * @param string              $aggregate
     * @param SearchFieldResolver $fieldResolver
     *
     * @return array [field name, field type, function, alias]
     */
    private function parseAggregate(string $aggregate, SearchFieldResolver $fieldResolver): array
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

        $fieldType = $fieldResolver->resolveFieldType($field);
        if (!\in_array($function, TokenInfo::getAggregatingFunctionsForType($fieldType), true)) {
            throw new InvalidFilterException(\sprintf(
                'The aggregating function "%s" is not supported for the field type "%s".',
                $function,
                $fieldType
            ));
        }

        return [
            $fieldResolver->resolveFieldName($field),
            $fieldType,
            $function,
            $parts[2] ?? $field . \ucfirst($function)
        ];
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
