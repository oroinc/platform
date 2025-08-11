<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Normalizes values of aggregated data returned by a search query.
 */
class NormalizeSearchAggregatedData implements ProcessorInterface
{
    public const OPERATION_NAME = 'normalize_search_aggregated_data';

    public const AGGREGATION_DATA_TYPES = 'aggregation_data_types';

    public function __construct(
        private readonly ValueTransformer $valueTransformer
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // a search aggregation filter was already applied to a search query
            return;
        }

        $infoRecords = $context->getInfoRecords();
        $aggregatedData = $infoRecords['aggregatedData'] ?? null;
        if (!$aggregatedData) {
            // no aggregated data
            return;
        }

        $infoRecords['aggregatedData'] = $this->normalizeAggregatedData(
            $aggregatedData,
            $context->get(self::AGGREGATION_DATA_TYPES) ?? [],
            $context->getNormalizationContext()
        );
        $context->setInfoRecords($infoRecords);
        $context->setProcessed(self::OPERATION_NAME);
    }

    private function normalizeAggregatedData(
        array $aggregatedData,
        array $dataTypes,
        array $normalizationContext
    ): array {
        foreach ($aggregatedData as $name => $value) {
            if (\is_array($value)) {
                // "count" aggregation
                $value = $this->normalizeCountValue($value);
                $aggregatedData[$name] = $value;
            }
            if (isset($dataTypes[$name]) && SearchQuery::TYPE_DATETIME === $dataTypes[$name]) {
                $aggregatedData[$name] = $this->normalizeDateTimeValue($value, $normalizationContext);
            }
        }

        return $aggregatedData;
    }

    private function normalizeCountValue(array $value): array
    {
        $normalizedValue = [];
        foreach ($value as $k => $v) {
            $normalizedValue[] = ['value' => \is_string($k) && !$k ? null : $k, 'count' => $v];
        }

        return $normalizedValue;
    }

    private function normalizeDateTimeValue(mixed $value, array $normalizationContext): mixed
    {
        if (\is_array($value)) {
            // "count" aggregation
            foreach ($value as $k => $v) {
                $val = $v['value'];
                if (\is_numeric($val)) {
                    $value[$k]['value'] = $this->normalizeTimestampDateTime($val, $normalizationContext);
                } elseif (\is_string($val) && $val) {
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
