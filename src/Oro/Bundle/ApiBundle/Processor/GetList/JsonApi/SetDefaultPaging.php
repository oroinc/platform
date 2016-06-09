<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Processor\GetList\SetDefaultPaging as BaseSetDefaultPaging;

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
