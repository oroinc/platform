<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\Rest;

use Oro\Bundle\ApiBundle\Processor\GetList\SetDefaultSorting as BaseSetDefaultSorting;

/**
 * Sets default sorting for REST API requests: sort = identifier field ASC.
 */
class SetDefaultSorting extends BaseSetDefaultSorting
{
    const SORT_FILTER_KEY = 'sort';

    /**
     * {@inheritdoc}
     */
    protected function getSortFilterKey()
    {
        return self::SORT_FILTER_KEY;
    }
}
