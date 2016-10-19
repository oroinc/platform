<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FieldAwareFilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Registers filters according to the "filters" configuration section.
 */
class RegisterConfiguredFilters extends RegisterFilters
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param FilterFactoryInterface $filterFactory
     * @param DoctrineHelper         $doctrineHelper
     */
    public function __construct(
        FilterFactoryInterface $filterFactory,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($filterFactory);
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $configOfFilters = $context->getConfigOfFilters();
        if (null === $configOfFilters || $configOfFilters->isEmpty()) {
            // a filters' configuration does not contains any data
            return;
        }

        if (!$configOfFilters->isExcludeAll()) {
            // it seems that filters' configuration was not normalized
            throw new RuntimeException(
                sprintf(
                    'Expected "all" exclusion policy for filters. Got: %s.',
                    $configOfFilters->getExclusionPolicy()
                )
            );
        }

        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        $filters = $context->getFilters();
        $fields = $configOfFilters->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($filters->has($fieldName)) {
                continue;
            }
            $filter = $this->createFilter($field, $field->getPropertyPath($fieldName));
            if (null !== $filter) {
                if ($filter instanceof FieldAwareFilterInterface) {
                    // @todo BAP-11881. Update this code when NEQ operator for to-many collection
                    // will be implemented in Oro\Bundle\ApiBundle\Filter\ComparisonFilter
                    if (null !== $metadata && $this->isCollection($metadata, $field->getPropertyPath($fieldName))) {
                        $filter->setSupportedOperators([StandaloneFilter::EQ]);
                    }
                }

                /**
                 * For filtering by relations only EQ and NEQ operators should be available.
                 */
                if (null !== $metadata
                    && in_array($fieldName, array_keys($this->doctrineHelper->getIndexedAssociations($metadata)))
                ) {
                    $filter->setSupportedOperators([
                        ComparisonFilter::EQ,
                        ComparisonFilter::NEQ
                    ]);
                }

                $filters->add($fieldName, $filter);
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $propertyPath
     *
     * @return bool
     */
    protected function isCollection(ClassMetadata $metadata, $propertyPath)
    {
        $isCollection = false;
        $path = explode('.', $propertyPath);
        foreach ($path as $fieldName) {
            if ($metadata->isCollectionValuedAssociation($fieldName)) {
                $isCollection = true;
                break;
            } elseif (!$metadata->hasAssociation($fieldName)) {
                break;
            }

            $metadata = $this->doctrineHelper->getEntityMetadataForClass(
                $metadata->getAssociationTargetClass($fieldName)
            );
        }

        return $isCollection;
    }
}
