<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Replaces the filter key for the identifier field with the "id" string
 * and sets the predefined description for this filter.
 */
class NormalizeIdFilterKey implements ProcessorInterface
{
    private const ID_FILTER_DESCRIPTION = 'Filter records by the identifier field';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $idFieldName = $this->getIdentifierFieldName($context);
        if ($idFieldName) {
            $filterCollection = $context->getFilters();
            $filters = $filterCollection->all();
            foreach ($filters as $filterKey => $filter) {
                if ($filter instanceof ComparisonFilter && $filter->getField() === $idFieldName) {
                    $filter->setDescription(self::ID_FILTER_DESCRIPTION);
                    $filterCollection->remove($filterKey);
                    $filterCollection->add(JsonApiDoc::ID, $filter);
                }
            }
        }
    }

    /**
     * @param Context $context
     *
     * @return string|null
     */
    private function getIdentifierFieldName(Context $context): ?string
    {
        $idFieldName = null;
        $config = $context->getConfig();
        if (null !== $config) {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (\count($idFieldNames) === 1) {
                $idFieldName = \reset($idFieldNames);
                $idField = $config->getField($idFieldName);
                if (null !== $idField && $idField->hasPropertyPath()) {
                    $idFieldName = $idField->getPropertyPath($idFieldName);
                }
            }
        } else {
            $entityClass = $context->getClassName();
            if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
                if (\count($idFieldNames) === 1) {
                    $idFieldName = \reset($idFieldNames);
                }
            }
        }

        return $idFieldName;
    }
}
