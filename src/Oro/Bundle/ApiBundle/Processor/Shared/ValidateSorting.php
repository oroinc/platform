<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\Provider\AssociationSortersProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that sorting by requested field(s) is supported.
 */
class ValidateSorting implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AssociationSortersProvider $associationSortersProvider;
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AssociationSortersProvider $associationSortersProvider,
        FilterNamesRegistry $filterNamesRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->associationSortersProvider = $associationSortersProvider;
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getSortFilterName();
        if (!$context->getFilters()->has($filterName)) {
            // no sort filter
            return;
        }

        $filterValue = $context->getFilterValues()->getOne($filterName);
        if (null === $filterValue) {
            // sorting is not requested
            return;
        }

        $unsupportedFields = $this->validateSortValues($filterValue, $context);
        if (!empty($unsupportedFields)) {
            $context->addError(
                Error::createValidationError(Constraint::SORT, $this->getValidationErrorMessage($unsupportedFields))
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey() ?: $filterName))
            );
        }
    }

    private function getValidationErrorMessage(array $unsupportedFields): string
    {
        return sprintf(
            'Sorting by "%s" field%s not supported.',
            implode(', ', $unsupportedFields),
            \count($unsupportedFields) === 1 ? ' is' : 's are'
        );
    }

    private function validateSortValues(FilterValue $filterValue, Context $context): array
    {
        $orderBy = $filterValue->getValue();
        if (empty($orderBy)) {
            return [];
        }

        $sorters = $context->getConfigOfSorters();

        $unsupportedFields = [];
        foreach ($orderBy as $fieldName => $direction) {
            $path = explode(ConfigUtil::PATH_DELIMITER, $fieldName);
            $propertyPath = \count($path) > 1
                ? $this->validateAssociationSorter($path, $context)
                : $this->validateSorter($fieldName, $sorters);
            if (!$propertyPath) {
                $unsupportedFields[] = $fieldName;
            }
        }

        return $unsupportedFields;
    }

    private function validateSorter(string $fieldName, ?SortersConfig $sorters): bool
    {
        $sorter = $sorters?->getField($fieldName);

        return null !== $sorter && !$sorter->isExcluded();
    }

    private function validateAssociationSorter(array $path, Context $context): bool
    {
        $entityClass = $context->getManageableEntityClass($this->doctrineHelper);
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return false;
        }

        $targetFieldName = array_pop($path);
        [$targetSorters] = $this->associationSortersProvider->getAssociationSorters(
            $path,
            $context,
            $this->doctrineHelper->getEntityMetadataForClass($entityClass)
        );

        return $this->validateSorter($targetFieldName, $targetSorters);
    }
}
