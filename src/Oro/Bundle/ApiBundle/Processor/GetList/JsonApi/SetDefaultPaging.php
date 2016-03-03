<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\Rest\SetDefaultPaging as RestSetDefaultPaging;
use Oro\Bundle\ApiBundle\Processor\GetList\SetDefaultPaging as BaseSetDefaultPaging;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Sets default paging for JSON API requests: page[number] = 1, page[size] = 10.
 */
class SetDefaultPaging extends BaseSetDefaultPaging
{
    const PAGE_NUMBER_FILTER_KEY = 'page[number]';
    const PAGE_SIZE_FILTER_KEY   = 'page[size]';

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
            // reuse REST API paging filters
            $filters = $context->getFilters();
            if ($filters->has(RestSetDefaultPaging::PAGE_SIZE_FILTER_KEY)) {
                $filter = $filters->get(RestSetDefaultPaging::PAGE_SIZE_FILTER_KEY);
                $filters->remove(RestSetDefaultPaging::PAGE_SIZE_FILTER_KEY);
                $filters->add($this->getPageSizeFilterKey(), $filter);
            }
            if ($filters->has(RestSetDefaultPaging::PAGE_NUMBER_FILTER_KEY)) {
                $filter = $filters->get(RestSetDefaultPaging::PAGE_NUMBER_FILTER_KEY);
                $filters->remove(RestSetDefaultPaging::PAGE_NUMBER_FILTER_KEY);
                $filters->add($this->getPageNumberFilterKey(), $filter);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPageNumberFilterKey()
    {
        return self::PAGE_NUMBER_FILTER_KEY;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPageSizeFilterKey()
    {
        return self::PAGE_SIZE_FILTER_KEY;
    }
}
