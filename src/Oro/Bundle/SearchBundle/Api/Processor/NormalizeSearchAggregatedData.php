<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Normalizes values of aggregated data returned by a search query.
 */
class NormalizeSearchAggregatedData implements ProcessorInterface
{
    private ValueTransformer $valueTransformer;
    private string $aggregationFilterName;

    public function __construct(
        ValueTransformer $valueTransformer,
        string $aggregationFilterName = 'aggregations'
    ) {
        $this->valueTransformer = $valueTransformer;
        $this->aggregationFilterName = $aggregationFilterName;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $infoRecords = $context->getInfoRecords();
        if (!isset($infoRecords['aggregatedData'])) {
            // no aggregated data
            return;
        }

        $aggregatedData = $infoRecords['aggregatedData'];
        if ($aggregatedData) {
            $filter = $context->getFilters()->get($this->aggregationFilterName);
            if ($filter instanceof SearchAggregationFilter) {
                $aggregatedData = $this->normalizeAggregatedData(
                    $aggregatedData,
                    $filter->getAggregationDataTypes(),
                    $context->getNormalizationContext()
                );
                $infoRecords['aggregatedData'] = $aggregatedData;
                $context->setInfoRecords($infoRecords);
            }
        }
    }

    private function normalizeAggregatedData(
        array $aggregatedData,
        array $dataTypes,
        array $normalizationContext
    ): array {
        foreach ($aggregatedData as $name => $value) {
            if (isset($dataTypes[$name]) && SearchQuery::TYPE_DATETIME === $dataTypes[$name]) {
                $aggregatedData[$name] = $this->normalizeDateTimeValue($value, $normalizationContext);
            }
        }

        return $aggregatedData;
    }

    private function normalizeDateTimeValue(mixed $value, array $normalizationContext)
    {
        if (\is_array($value)) {
            // "count" aggregation
            foreach ($value as $k => $v) {
                $val = $v['value'];
                if (\is_numeric($val)) {
                    $value[$k]['value'] = $this->normalizeTimestampDateTime($val, $normalizationContext);
                } elseif (\is_string($val)) {
                    $value[$k]['value'] = $this->normalizeStringDateTime($val, $normalizationContext);
                }
            }
            return $value;
        }

        if (\is_numeric($value)) {
            return $this->normalizeTimestampDateTime($value, $normalizationContext);
        }

        if (\is_string($value)) {
            return $this->normalizeStringDateTime($value, $normalizationContext);
        }

        return $value;
    }

    private function normalizeTimestampDateTime(float|int $timestamp, array $normalizationContext): ?string
    {
        return $this->valueTransformer->transformValue(
            new \DateTime('@' . $timestamp),
            DataType::DATETIME,
            $normalizationContext
        );
    }

    private function normalizeStringDateTime(string $dateTime, array $normalizationContext): ?string
    {
        return $this->valueTransformer->transformValue(
            new \DateTime($dateTime, new \DateTimeZone('UTC')),
            DataType::DATETIME,
            $normalizationContext
        );
    }
}
