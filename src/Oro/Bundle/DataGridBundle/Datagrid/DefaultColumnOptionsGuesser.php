<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface as Property;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

class DefaultColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        switch ($type) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                $frontendType = Property::TYPE_INTEGER;
                break;
            case 'decimal':
            case 'float':
                $frontendType = Property::TYPE_DECIMAL;
                break;
            case 'boolean':
                $frontendType = Property::TYPE_BOOLEAN;
                break;
            case 'date':
                $frontendType = Property::TYPE_DATE;
                break;
            case 'datetime':
                $frontendType = Property::TYPE_DATETIME;
                break;
            case 'money':
                $frontendType = Property::TYPE_CURRENCY;
                break;
            case 'percent':
                $frontendType = Property::TYPE_PERCENT;
                break;
            default:
                $frontendType = Property::TYPE_STRING;
                break;
        }

        $options = [
            'frontend_type' => $frontendType
        ];

        return new ColumnGuess($options, ColumnGuess::LOW_CONFIDENCE);
    }
}
