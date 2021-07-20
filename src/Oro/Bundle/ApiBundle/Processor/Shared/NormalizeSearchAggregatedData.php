<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\SearchAggregationFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Normalizes values of aggregated data returned by search query.
 */
class NormalizeSearchAggregatedData implements ProcessorInterface
{
    /** @var ValueTransformer */
    private $valueTransformer;

    /** @var string */
    private $aggregationFilterName;

    public function __construct(
        ValueTransformer $valueTransformer,
        string $aggregationFilterName = 'aggregations'
    ) {
        $this->valueTransformer = $valueTransformer;
        $this->aggregationFilterName = $aggregationFilterName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
                if (\is_array($value)) {
                    // "count" aggregation
                    foreach ($value as $k => $v) {
                        $val = $v['value'];
                        if (\is_numeric($val)) {
                            $value[$k]['value'] = $this->normalizeDateTime($val, $normalizationContext);
                        }
                    }
                    $aggregatedData[$name] = $value;
                } elseif (\is_numeric($value)) {
                    $aggregatedData[$name] = $this->normalizeDateTime($value, $normalizationContext);
                }
            }
        }

        return $aggregatedData;
    }

    /**
     * @param float|int $timestamp
     * @param array     $normalizationContext
     *
     * @return string|null
     */
    private function normalizeDateTime($timestamp, array $normalizationContext): ?string
    {
        return $this->valueTransformer->transformValue(
            new \DateTime('@' . $timestamp),
            DataType::DATETIME,
            $normalizationContext
        );
    }
}
