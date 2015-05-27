<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class CommandWithArgsFilter extends StringFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        if (!array_key_exists('fields', $params)
            || (array_key_exists('fields', $params)
            && !is_array($params['fields'])
            && count($params['fields']) != 2)
        ) {
            throw new \InvalidArgumentException("Argument 'fileds' must be configured properly");
        }

        $params[FilterUtility::DATA_NAME_KEY] = sprintf('CONCAT(%s)', implode(',', $params['fields']));

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function parseValue($comparisonType, $value)
    {
        $valuesArr = explode(' ', $value);

        $command = array_shift($valuesArr);
        $args    = '';

        if (count($valuesArr) > 0) {
            $args = '["' . implode('","', $valuesArr);
        }

        $value = $command . $args;

        return parent::parseValue($comparisonType, $value);
    }
}
