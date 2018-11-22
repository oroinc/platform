<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that sorting by requested field(s) is supported.
 */
class ValidateSorting implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param ConfigProvider      $configProvider
     * @param FilterNamesRegistry $filterNamesRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        FilterNamesRegistry $filterNamesRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
        $this->filterNamesRegistry = $filterNamesRegistry;
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

        $sortFilterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getSortFilterName();
        if (!$context->getFilters()->has($sortFilterName)) {
            // no sort filter
            return;
        }

        $sortFilterValue = $context->getFilterValues()->get($sortFilterName);
        if (null === $sortFilterValue) {
            // sorting is not requested
            return;
        }

        $unsupportedFields = $this->validateSortValues($sortFilterValue, $context);
        if (!empty($unsupportedFields)) {
            $context->addError(
                Error::createValidationError(Constraint::SORT, $this->getValidationErrorMessage($unsupportedFields))
                    ->setSource(ErrorSource::createByParameter($sortFilterValue->getSourceKey() ?: $sortFilterName))
            );
        }
    }

    /**
     * @param string[] $unsupportedFields
     *
     * @return string
     */
    private function getValidationErrorMessage(array $unsupportedFields): string
    {
        return \sprintf(
            'Sorting by "%s" field%s not supported.',
            \implode(', ', $unsupportedFields),
            \count($unsupportedFields) === 1 ? ' is' : 's are'
        );
    }

    /**
     * @param FilterValue $filterValue
     * @param Context     $context
     *
     * @return string[] The list of fields that cannot be used for sorting
     */
    private function validateSortValues(FilterValue $filterValue, Context $context): array
    {
        $orderBy = $filterValue->getValue();
        if (empty($orderBy)) {
            return [];
        }

        $sorters = $context->getConfigOfSorters();

        $unsupportedFields = [];
        foreach ($orderBy as $fieldName => $direction) {
            $path = \explode('.', $fieldName);
            $propertyPath = \count($path) > 1
                ? $this->validateAssociationSorter($path, $context)
                : $this->validateSorter($fieldName, $sorters);
            if (!$propertyPath) {
                $unsupportedFields[] = $fieldName;
            } elseif ($propertyPath !== $fieldName) {
                $this->renameSortField($filterValue, $fieldName, $propertyPath);
            }
        }

        return $unsupportedFields;
    }

    /**
     * @param FilterValue $filterValue
     * @param string      $oldFieldName
     * @param string      $newFieldName
     */
    private function renameSortField(FilterValue $filterValue, string $oldFieldName, string $newFieldName): void
    {
        $updatedOrderBy = [];
        $orderBy = $filterValue->getValue();
        foreach ($orderBy as $fieldName => $direction) {
            if ($fieldName === $oldFieldName) {
                $fieldName = $newFieldName;
            }
            $updatedOrderBy[$fieldName] = $direction;
        }
        $filterValue->setValue($updatedOrderBy);
    }

    /**
     * @param string             $fieldName
     * @param SortersConfig|null $sorters
     *
     * @return string|null The real field name if the sorting is allowed; otherwise, NULL
     */
    private function validateSorter(string $fieldName, SortersConfig $sorters = null): ?string
    {
        if (null === $sorters) {
            return null;
        }

        $sorter = $sorters->getField($fieldName);
        if (null === $sorter || $sorter->isExcluded()) {
            return null;
        }

        return $sorter->getPropertyPath($fieldName);
    }

    /**
     * @param string[] $path
     * @param Context  $context
     *
     * @return string|null The real association path if the sorting is allowed; otherwise, NULL
     */
    private function validateAssociationSorter(array $path, Context $context): ?string
    {
        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return null;
        }

        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $targetFieldName = \array_pop($path);
        list($targetSorters, $associations) = $this->getAssociationSorters($path, $context, $metadata);
        $targetFieldName = $this->validateSorter($targetFieldName, $targetSorters);
        if (!$targetFieldName) {
            return null;
        }

        return $associations . '.' . $targetFieldName;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return array [sorters config, associations]
     */
    private function getAssociationSorters(array $path, Context $context, ClassMetadata $metadata): array
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new SortersConfigExtra()
        ];

        $config = $context->getConfig();
        $sorters = null;
        $associations = [];

        foreach ($path as $fieldName) {
            if (!$config->hasField($fieldName)) {
                return [null, null];
            }

            $associationName = $config->getField($fieldName)->getPropertyPath($fieldName);
            if (!$metadata->hasAssociation($associationName)) {
                return [null, null];
            }

            $targetClass = $metadata->getAssociationTargetClass($associationName);
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass, false);
            if (!$metadata) {
                return [null, null];
            }

            $targetConfig = $this->configProvider->getConfig(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfigExtras
            );
            if (!$targetConfig->hasDefinition()) {
                return [null, null];
            }

            $config = $targetConfig->getDefinition();
            $sorters = $targetConfig->getSorters();
            $associations[] = $associationName;
        }

        return [$sorters, \implode('.', $associations)];
    }
}
