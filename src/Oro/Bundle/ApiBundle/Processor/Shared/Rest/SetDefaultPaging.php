<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultPaging as BaseSetDefaultPaging;

/**
 * Sets default paging for REST API requests: page = 1, limit = 10.
 */
class SetDefaultPaging extends BaseSetDefaultPaging
{
    const PAGE_NUMBER_FILTER_KEY = 'page';
    const PAGE_SIZE_FILTER_KEY   = 'limit';

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
