<?php

namespace Oro\Bundle\SoapBundle\Request\Parameters\Filter;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

/**
 * Filters request parameters by resolving entity class names.
 *
 * Uses the {@see EntityClassNameHelper} to resolve entity class names from various formats
 * (such as short names or aliases) to fully qualified class names, handling both
 * single values and arrays of values.
 */
class EntityClassParameterFilter implements ParameterFilterInterface
{
    /** @var EntityClassNameHelper */
    protected $helper;

    public function __construct(EntityClassNameHelper $helper)
    {
        $this->helper = $helper;
    }

    #[\Override]
    public function filter($rawValue, $operator)
    {
        if (is_array($rawValue)) {
            return array_map(
                function ($val) {
                    return $this->helper->resolveEntityClass($val);
                },
                $rawValue
            );
        } else {
            return $this->helper->resolveEntityClass($rawValue);
        }
    }
}
