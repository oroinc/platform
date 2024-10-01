<?php

namespace Oro\Bundle\FilterBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

/**
 * Guesses filtering options based on a column type.
 */
class DefaultColumnFilteringOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function guessFilter($class, $property, $type)
    {
        switch ($type) {
            case 'smallint':
                $options = [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_SMALLINT,
                        'source_type' => $type
                    ]
                ];
                break;
            case 'number':
            case 'bigint':
                $options = [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_BIGINT,
                        'source_type' => $type
                    ]
                ];
                break;
            case 'integer':
                $options = [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_INTEGER,
                        'source_type' => $type
                    ]
                ];
                break;
            case 'decimal':
            case 'float':
                $options = [
                    'type'    => 'number-range',
                    'options' => [
                        'data_type' => NumberFilterType::DATA_DECIMAL,
                        'source_type' => $type
                    ]
                ];
                break;
            case 'boolean':
                $options = [
                    'type' => 'boolean'
                ];
                break;
            case 'date':
                $options = [
                    'type' => 'date'
                ];
                break;
            case 'datetime':
                $options = [
                    'type' => 'datetime'
                ];
                break;
            case 'money':
                $options = [
                    'type' => 'number-range',
                    'source_type' => $type
                ];
                break;
            case 'percent':
                $options = [
                    'type' => 'percent',
                    'source_type' => $type
                ];
                break;
            default:
                $options = [
                    'type' => 'string'
                ];
                break;
        }

        return new ColumnGuess($options, ColumnGuess::LOW_CONFIDENCE);
    }
}
