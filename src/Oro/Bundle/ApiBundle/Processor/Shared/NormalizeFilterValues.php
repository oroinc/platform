<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SpecialHandlingFilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
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

    /** @var Context */
    private $context;

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

        $this->context = $context;
        try {
            $this->normalizeFilterValues();
        } finally {
            $this->context = null;
        }
    }

    private function normalizeFilterValues(): void
    {
        $requestType = $this->context->getRequestType();
        $metadata = $this->context->getMetadata();
        $filters = $this->context->getFilters();
        $filterValues = $this->context->getFilterValues()->getAll();
        foreach ($filterValues as $filterKey => $filterValue) {
            if ($filters->has($filterKey)) {
                $filter = $filters->get($filterKey);
                if ($filter instanceof StandaloneFilter && !$filter instanceof SpecialHandlingFilterInterface) {
                    try {
                        $value = $this->normalizeFilterValue(
                            $requestType,
                            $filter,
                            $filterKey,
                            $filterValue->getValue(),
                            $filterValue->getOperator(),
                            $metadata
                        );
                        $filterValue->setValue($value);
                    } catch (\Exception $e) {
                        $this->context->addError(
                            $this->createFilterError($filterKey, $filterValue)->setInnerException($e)
                        );
                    }
                }
            } else {
                $this->context->addError(
                    $this->createFilterError($filterKey, $filterValue)->setDetail('The filter is not supported.')
                );
            }
        }
    }

    /**
     * @param RequestType         $requestType
     * @param StandaloneFilter    $filter
     * @param string              $path
     * @param mixed               $value
     * @param string|null         $operator
     * @param EntityMetadata|null $metadata
     *
     * @return mixed
     */
    private function normalizeFilterValue(
        RequestType $requestType,
        StandaloneFilter $filter,
        string $path,
        $value,
        ?string $operator,
        ?EntityMetadata $metadata
    ) {
        $dataType = $filter->getDataType();
        $isArrayAllowed = $filter->isArrayAllowed($operator);
        $isRangeAllowed = $filter->isRangeAllowed($operator);
        if (FilterOperator::EXISTS === $operator) {
            $dataType = DataType::BOOLEAN;
            $isArrayAllowed = false;
            $isRangeAllowed = false;
        } elseif (null !== $metadata && $filter instanceof ComparisonFilter) {
            $fieldName = $filter->getField();
            if ($fieldName) {
                if ($metadata->hasAssociation($fieldName)) {
                    return $this->normalizeIdentifierValue(
                        $path,
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
                            $path,
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
     * @param string         $path
     * @param mixed          $value
     * @param bool           $isArrayAllowed
     * @param bool           $isRangeAllowed
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     *
     * @return mixed
     */
    private function normalizeIdentifierValue(
        string $path,
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
            $hasNotResolvedIdentifiers = false;
            foreach ($value as $val) {
                $normalizedId = $entityIdTransformer->reverseTransform($val, $metadata);
                if (null === $normalizedId) {
                    $hasNotResolvedIdentifiers = true;
                    $normalizedId = $this->getNotExistingEntityIdentifier($metadata);
                }
                $normalizedValue[] = $normalizedId;
            }
            if ($hasNotResolvedIdentifiers) {
                $this->context->addNotResolvedIdentifier(
                    ConfigUtil::FILTERS . ConfigUtil::PATH_DELIMITER . $path,
                    new NotResolvedIdentifier($value, $metadata->getClassName())
                );
            }

            return $normalizedValue;
        }

        if ($value instanceof Range) {
            $normalizedFromId = $entityIdTransformer->reverseTransform($value->getFromValue(), $metadata);
            $normalizedToId = $entityIdTransformer->reverseTransform($value->getToValue(), $metadata);
            if (null === $normalizedFromId || null === $normalizedToId) {
                $this->context->addNotResolvedIdentifier(
                    ConfigUtil::FILTERS . ConfigUtil::PATH_DELIMITER . $path,
                    new NotResolvedIdentifier(
                        new Range($value->getFromValue(), $value->getToValue()),
                        $metadata->getClassName()
                    )
                );
                $normalizedFromId = $this->getNotExistingEntityIdentifier($metadata);
                $normalizedToId = $normalizedFromId;
            }
            $value->setFromValue($normalizedFromId);
            $value->setToValue($normalizedToId);

            return $value;
        }

        $normalizedId = $entityIdTransformer->reverseTransform($value, $metadata);
        if (null === $normalizedId) {
            $this->context->addNotResolvedIdentifier(
                ConfigUtil::FILTERS . ConfigUtil::PATH_DELIMITER . $path,
                new NotResolvedIdentifier($value, $metadata->getClassName())
            );
            $normalizedId = $this->getNotExistingEntityIdentifier($metadata);
        }

        return $normalizedId;
    }

    /**
     * @param EntityMetadata $metadata
     *
     * @return mixed
     */
    private function getNotExistingEntityIdentifier(EntityMetadata $metadata)
    {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            return $this->getNotExistingIdentifierFieldValue($metadata, reset($idFieldNames));
        }

        $result = [];
        foreach ($idFieldNames as $idFieldName) {
            $result[$idFieldName] = $this->getNotExistingIdentifierFieldValue($metadata, $idFieldName);
        }

        return $result;
    }

    /**
     * @param EntityMetadata $metadata
     * @param string         $idFieldName
     *
     * @return mixed
     */
    private function getNotExistingIdentifierFieldValue(EntityMetadata $metadata, string $idFieldName)
    {
        return DataType::STRING === $metadata->getProperty($idFieldName)->getDataType()
            ? ''
            : 0;
    }

    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    private function createFilterError(string $filterKey, FilterValue $filterValue): Error
    {
        return Error::createValidationError(Constraint::FILTER)
            ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey() ?: $filterKey));
    }
}
