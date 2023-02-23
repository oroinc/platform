<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\Sorter\FiltersSorterRegistry;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FieldFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\NamedValueFilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Adds filters to ApiDoc annotation.
 */
class RestDocFiltersHandler
{
    private RestDocViewDetector $docViewDetector;
    private ValueNormalizer $valueNormalizer;
    private ApiDocDataTypeConverter $dataTypeConverter;
    private FiltersSorterRegistry $sorterRegistry;

    public function __construct(
        RestDocViewDetector $docViewDetector,
        ValueNormalizer $valueNormalizer,
        ApiDocDataTypeConverter $dataTypeConverter,
        FiltersSorterRegistry $sorterRegistry
    ) {
        $this->docViewDetector = $docViewDetector;
        $this->valueNormalizer = $valueNormalizer;
        $this->dataTypeConverter = $dataTypeConverter;
        $this->sorterRegistry = $sorterRegistry;
    }

    public function handle(ApiDoc $annotation, FilterCollection $filters, EntityMetadata $metadata): void
    {
        if (!$filters->isEmpty()) {
            $this->addFilters($annotation, $filters, $metadata);
        }
        $this->sortFilters($annotation);
    }

    private function addFilters(ApiDoc $annotation, FilterCollection $filters, EntityMetadata $metadata): void
    {
        foreach ($filters as $key => $filter) {
            if ($filter instanceof StandaloneFilter) {
                if ($filter instanceof NamedValueFilterInterface) {
                    $key .= sprintf('[%s]', $filter->getFilterValueName());
                }
                $annotation->addFilter($key, $this->getFilterOptions($filter, $metadata));
            }
        }
    }

    private function sortFilters(ApiDoc $annotation): void
    {
        $filters = $annotation->getFilters();
        if (\count($filters) > 1) {
            $sorter = $this->sorterRegistry->getSorter($this->docViewDetector->getRequestType());
            if (null === $sorter) {
                ksort($filters);
            } else {
                $filters = $sorter->sortFilters($filters);
            }
            ApiDocAnnotationUtil::setFilters($annotation, $filters);
        }
    }

    private function getFilterOptions(StandaloneFilter $filter, EntityMetadata $metadata): array
    {
        $dataType = $filter->getDataType();
        $isArrayAllowed = $filter->isArrayAllowed();
        $isRangeAllowed = $filter->isRangeAllowed();
        $options = [
            'description' => $this->getFilterDescription($filter->getDescription()),
            'requirement' => $this->valueNormalizer->getRequirement(
                $dataType,
                $this->docViewDetector->getRequestType(),
                $isArrayAllowed,
                $isRangeAllowed
            )
        ];
        if ($filter instanceof FieldFilterInterface) {
            $options['type'] = $this->getFilterType($dataType, $isArrayAllowed, $isRangeAllowed);
        }
        $operators = $this->getFilterOperators($filter);
        if (!empty($operators)) {
            $options['operators'] = implode(',', $operators);
        }
        if ($filter instanceof StandaloneFilterWithDefaultValue) {
            $default = $filter->getDefaultValueString();
            if (!empty($default)) {
                $options['default'] = $default;
            }
        }
        if ($filter instanceof FieldAwareFilterInterface) {
            $association = $metadata->getAssociation($filter->getField());
            if (null !== $association && !DataType::isAssociationAsField($association->getDataType())) {
                $targetEntityTypes = $this->getFilterTargetEntityTypes(
                    $association->getAcceptableTargetClassNames()
                );
                if (!empty($targetEntityTypes)) {
                    $options['relation'] = implode(',', $targetEntityTypes);
                }
            }
        }

        return $options;
    }

    private function getFilterDescription(?string $description): string
    {
        return $description ?? '';
    }

    private function getFilterType(string $dataType, bool $isArrayAllowed, bool $isRangeAllowed): string
    {
        $dataType = $this->dataTypeConverter->convertDataType(
            $dataType,
            $this->docViewDetector->getView()
        );

        $result = '%1$s';
        if ($isArrayAllowed) {
            $result .= ' or array';
        }
        if ($isRangeAllowed) {
            $result .= ' or range';
        }

        return sprintf($result, $dataType);
    }

    /**
     * @param string[] $targetClassNames
     *
     * @return string[]
     */
    private function getFilterTargetEntityTypes(array $targetClassNames): array
    {
        $targetEntityTypes = [];
        foreach ($targetClassNames as $targetClassName) {
            $targetEntityType = $this->getEntityType($targetClassName);
            if ($targetEntityType) {
                $targetEntityTypes[] = $targetEntityType;
            }
        }

        return $targetEntityTypes;
    }

    private function getEntityType(string $entityClass): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $this->docViewDetector->getRequestType()
        );
    }

    /**
     * @param StandaloneFilter $filter
     *
     * @return string[]
     */
    private function getFilterOperators(StandaloneFilter $filter): array
    {
        $operators = $filter->getSupportedOperators();
        if (empty($operators)) {
            return $operators;
        }
        if (\count($operators) === 1 && $operators[0] === FilterOperator::EQ) {
            return [];
        }

        return $operators;
    }
}
