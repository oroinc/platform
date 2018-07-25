<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts values of all requested filters according to the type of the filters.
 * Validates that all requested filters are supported.
 */
class NormalizeFilterValues implements ProcessorInterface
{
    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /**
     * @param ValueNormalizer             $valueNormalizer
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $requestType = $context->getRequestType();
        $metadata = $context->getMetadata();
        $filters = $context->getFilters();
        $filterValues = $context->getFilterValues()->getAll();
        foreach ($filterValues as $filterKey => $filterValue) {
            if ($filters->has($filterKey)) {
                $filter = $filters->get($filterKey);
                if ($filter instanceof StandaloneFilter) {
                    try {
                        $value = $this->normalizeFilterValue(
                            $requestType,
                            $filter,
                            $filterValue->getValue(),
                            $filterValue->getOperator(),
                            $metadata
                        );
                        $filterValue->setValue($value);
                    } catch (\Exception $e) {
                        $context->addError(
                            $this->createFilterError($filterKey, $filterValue)->setInnerException($e)
                        );
                    }
                }
            } else {
                $context->addError(
                    $this->createFilterError($filterKey, $filterValue)->setDetail('The filter is not supported.')
                );
            }
        }
    }

    /**
     * @param RequestType         $requestType
     * @param StandaloneFilter    $filter
     * @param mixed               $value
     * @param string|null         $operator
     * @param EntityMetadata|null $metadata
     *
     * @return mixed
     */
    private function normalizeFilterValue(
        RequestType $requestType,
        StandaloneFilter $filter,
        $value,
        ?string $operator,
        ?EntityMetadata $metadata
    ) {
        $dataType = $filter->getDataType();
        $isArrayAllowed = $filter->isArrayAllowed($operator);
        $isRangeAllowed = $filter->isRangeAllowed($operator);
        if (ComparisonFilter::EXISTS === $operator) {
            $dataType = DataType::BOOLEAN;
            $isArrayAllowed = false;
            $isRangeAllowed = false;
        } elseif (null !== $metadata && $filter instanceof ComparisonFilter) {
            $fieldName = $filter->getField();
            if ($fieldName) {
                if ($metadata->hasAssociation($fieldName)) {
                    return $this->normalizeIdentifierValue(
                        $value,
                        $isArrayAllowed,
                        $isRangeAllowed,
                        $requestType,
                        $metadata->getAssociation($fieldName)->getTargetMetadata()
                    );
                }
                $idFieldNames = $metadata->getIdentifierFieldNames();
                if (\count($idFieldNames) === 1) {
                    $property = $metadata->getPropertyByPropertyPath($fieldName);
                    if (null !== $property && $property->getName() === $idFieldNames[0]) {
                        return $this->normalizeIdentifierValue(
                            $value,
                            $isArrayAllowed,
                            $isRangeAllowed,
                            $requestType,
                            $metadata
                        );
                    }
                }
            }
        }

        return $this->valueNormalizer->normalizeValue(
            $value,
            $dataType,
            $requestType,
            $isArrayAllowed,
            $isRangeAllowed
        );
    }

    /**
     * @param mixed          $value
     * @param bool           $isArrayAllowed
     * @param bool           $isRangeAllowed
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     *
     * @return mixed
     */
    private function normalizeIdentifierValue(
        $value,
        bool $isArrayAllowed,
        bool $isRangeAllowed,
        RequestType $requestType,
        EntityMetadata $metadata
    ) {
        $value = $this->valueNormalizer->normalizeValue(
            $value,
            DataType::STRING,
            $requestType,
            $isArrayAllowed,
            $isRangeAllowed
        );

        $entityIdTransformer = $this->getEntityIdTransformer($requestType);

        if (\is_array($value)) {
            $normalizedValue = [];
            foreach ($value as $val) {
                $normalizedValue[] = $entityIdTransformer->reverseTransform($val, $metadata);
            }

            return $normalizedValue;
        }

        if ($value instanceof Range) {
            $value->setFromValue($entityIdTransformer->reverseTransform($value->getFromValue(), $metadata));
            $value->setToValue($entityIdTransformer->reverseTransform($value->getToValue(), $metadata));

            return $value;
        }

        return $entityIdTransformer->reverseTransform($value, $metadata);
    }

    /**
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    /**
     * @param string      $filterKey
     * @param FilterValue $filterValue
     *
     * @return Error
     */
    private function createFilterError(string $filterKey, FilterValue $filterValue): Error
    {
        return Error::createValidationError(Constraint::FILTER)
            ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey() ?: $filterKey));
    }
}
