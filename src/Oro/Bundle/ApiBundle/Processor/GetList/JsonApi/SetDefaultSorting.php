<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\Rest\SetDefaultSorting as RestSetDefaultSorting;
use Oro\Bundle\ApiBundle\Processor\GetList\SetDefaultSorting as BaseSetDefaultSorting;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Sets default sorting for JSON API requests: sort = id ASC.
 */
class SetDefaultSorting extends BaseSetDefaultSorting
{
    const SORT_FILTER_KEY = 'sort';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        if (!$context->getRequestType()->contains(RequestType::REST)) {
            parent::process($context);
        } else {
            // reuse REST API sorting filter
            $filters       = $context->getFilters();
            $sortFilterKey = $this->getSortFilterKey();
            if ($sortFilterKey !== RestSetDefaultSorting::SORT_FILTER_KEY
                && $filters->has(RestSetDefaultSorting::SORT_FILTER_KEY)
            ) {
                $filter = $filters->get(RestSetDefaultSorting::SORT_FILTER_KEY);
                $filters->remove(RestSetDefaultSorting::SORT_FILTER_KEY);
                $filters->add($sortFilterKey, $filter);
            }
            if ($filters->has($sortFilterKey)) {
                $filter = $filters->get($sortFilterKey);
                if ($filter instanceof SortFilter) {
                    $entityClass = $context->getClassName();
                    $filter->setDefaultValue(
                        function () use ($entityClass) {
                            return $this->getDefaultValue($entityClass);
                        }
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSortFilterKey()
    {
        return self::SORT_FILTER_KEY;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue($entityClass)
    {
        $result = [];

        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        foreach ($idFieldNames as $fieldName) {
            $result[$fieldName] = Criteria::ASC;
        }

        return $result;
    }
}
