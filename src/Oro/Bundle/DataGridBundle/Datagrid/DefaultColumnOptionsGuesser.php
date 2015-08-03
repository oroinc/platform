<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

class DefaultColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /** @var array */
    protected $typeToFrontendTypeMap = [
        'integer'  => Property::TYPE_INTEGER,
        'smallint' => Property::TYPE_INTEGER,
        'bigint'   => Property::TYPE_INTEGER,
        'decimal'  => Property::TYPE_DECIMAL,
        'float'    => Property::TYPE_DECIMAL,
        'boolean'  => Property::TYPE_BOOLEAN,
        'date'     => Property::TYPE_DATE,
        'datetime' => Property::TYPE_DATETIME,
        'time'     => Property::TYPE_TIME,
        'money'    =>  Property::TYPE_CURRENCY,
        'percent'  => Property::TYPE_PERCENT,
        'simple_array' => Property::TYPE_SIMPLE_ARRAY,
        'array'        => Property::TYPE_ARRAY,
        'json_array'   => Property::TYPE_ARRAY,
    ];

    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        $options = [
            'frontend_type' => $this->getFrontendType($type),
        ];

        return new ColumnGuess($options, ColumnGuess::LOW_CONFIDENCE);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getFrontendType($type)
    {
        if (isset($this->typeToFrontendTypeMap[$type])) {
            return $this->typeToFrontendTypeMap[$type];
        }

        return Property::TYPE_STRING;
    }
}
