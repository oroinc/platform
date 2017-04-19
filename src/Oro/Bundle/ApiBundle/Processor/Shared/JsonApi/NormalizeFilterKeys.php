<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Encloses filters keys by the "filter[%s]" pattern.
 * Replaces the filter key for the identifier field with "filter[id]".
 */
class NormalizeFilterKeys implements ProcessorInterface
{
    const FILTER_KEY_TEMPLATE = 'filter[%s]';

    const ID_FILTER_DESCRIPTION = 'Filter records by the identifier field';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

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

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $filterCollection = $context->getFilters();
        $idFieldName = $this->getIdFieldName($context->getClassName());

        $filters = $filterCollection->all();
        foreach ($filters as $filterKey => $filter) {
            $filterCollection->remove($filterKey);
            if ($filter instanceof ComparisonFilter && $filter->getField() === $idFieldName) {
                $filterKey = JsonApiDoc::ID;
                $filter->setDescription(self::ID_FILTER_DESCRIPTION);
            }
            $filterCollection->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $filterKey),
                $filter
            );
        }
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function getIdFieldName($entityClass)
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return null;
        }

        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);

        return reset($idFieldNames);
    }
}
