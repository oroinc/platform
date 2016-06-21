<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultSorting as BaseSetDefaultSorting;

/**
 * Sets default sorting for JSON API requests: sort = id ASC.
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

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue($entityClass)
    {
        return ['id' => 'ASC'];
    }
}
