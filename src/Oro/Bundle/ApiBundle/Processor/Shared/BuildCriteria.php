<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * Applies all requested filters to the Criteria object.
 */
class BuildCriteria implements ProcessorInterface
{
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

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $filterValues = $context->getFilterValues();

        $filters = $context->getFilters();
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $filter->apply($criteria, $filterValues->get($filterKey));
                $filterValues->remove($filterKey);
            }
        }

        // Process unknown filters
        foreach ($filterValues->getAll() as $filterKey => $filterValue) {
            /** @var FilterValue $filterValue */
            if ($filters->has($filterKey)) {
                continue;
            }

            $filterValueDataType = $this->getFilterValueDataType($context, $filterKey, $filterValue);
            if (!$filterValueDataType) {
                $this->addContextDataTypeNotFoundError($context, $filterKey);
                continue;
            }

            /** @var ComparisonFilter $filter */
            $filter = new ComparisonFilter($filterValueDataType);
            $filter->setField($filterValue->getPath());
            $filter->apply($criteria, $filterValue);

            $filters->add($filterKey, $filter);
        }
    }

    /**
     * @param Context     $context
     * @param string      $filterKey
     * @param FilterValue $filterValue
     *
     * @return string|null
     */
    protected function getFilterValueDataType($context, $filterKey, $filterValue)
    {
        $metadata = $context->getMetadata();

        $filterParts = explode('.', $filterValue->getPath());
        $filterPartsCount = count($filterParts);

        foreach ($filterParts as $index => $fieldName) {
            //the last part reached
            if ($index === $filterPartsCount - 1) {
                break;
            }

            //all parts, except last one should be associations
            if (!$metadata->hasAssociation($fieldName)) {
                $this->addContextAssociationError($context, $fieldName, $filterKey);
                break;
            }

            $metadata = $context->getMetadata($metadata->getAssociation($fieldName)->getTargetClassName());
        }

        $fieldName = end($filterParts);
        if (!$metadata->hasAssociation($fieldName) && !$metadata->hasField($fieldName)) {
            $this->addContextResourceError($context, $fieldName, $filterKey);

            return null;
        }

        if ($metadata->hasAssociation($fieldName)) {
            /** @var FiltersConfig $config */
            $config = $context->getConfigOfFilters($metadata->getAssociation($fieldName)->getTargetClassName());

            /** @var EntityMetadata $meta */
            $meta = $metadata->getAssociation($fieldName)->getTargetMetadata();
            foreach ($meta->getIdentifierFieldNames() as $identifier) {
                if ($config->hasField($identifier)) {
                    return $config->getField($identifier)->getDataType();
                }
            }
        } else {
            /** @var FiltersConfig $config */
            $config = $context->getConfigOfFilters($metadata->getClassName());
            if (!$config->hasField($fieldName)) {
                $this->addContextFilterNotAllowedError($context, $fieldName, $filterKey);
                return null;
            }

            return $config->getField($fieldName)->getDataType();
        }

        return null;
    }

    /**
     * @param Context $context
     * @param string  $filterKey
     */
    protected function addContextDataTypeNotFoundError($context, $filterKey)
    {
        /** @var Context $context */
        $context->addError(
            Error::createValidationError(
                Constraint::FILTER,
                'Filter data type not specified and could not be detected automatically.'
            )->setSource(ErrorSource::createByParameter($filterKey))
        );
    }

    /**
     * @param Context $context
     * @param string  $fieldName
     * @param string  $filterKey
     */
    protected function addContextAssociationError($context, $fieldName, $filterKey)
    {
        /** @var Context $context */
        $context->addError(
            Error::createValidationError(
                Constraint::FILTER,
                sprintf('All resource parts (except last), should be an association, see the "%s".', $fieldName)
            )->setSource(ErrorSource::createByParameter($filterKey))
        );
    }

    /**
     * @param Context $context
     * @param string  $fieldName
     * @param string  $filterKey
     */
    protected function addContextResourceError($context, $fieldName, $filterKey)
    {
        /** @var Context $context */
        $context->addError(
            Error::createValidationError(
                Constraint::FILTER,
                sprintf('Unknown resource "%s".', $fieldName)
            )->setSource(ErrorSource::createByParameter($filterKey))
        );
    }

    /**
     * @param Context $context
     * @param string  $fieldName
     * @param string  $filterKey
     */
    protected function addContextFilterNotAllowedError($context, $fieldName, $filterKey)
    {
        /** @var Context $context */
        $context->addError(
            Error::createValidationError(
                Constraint::FILTER,
                sprintf('Filtering by "%s" field is not supported.', $fieldName)
            )->setSource(ErrorSource::createByParameter($filterKey))
        );
    }
}
