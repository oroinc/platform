<?php

namespace Oro\Bundle\CurrencyBundle\Grid;

use Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types\MoneyValueType;
use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

/**
 * Provides automatic configuration for money value datagrid columns.
 *
 * This guesser automatically configures formatter and filter options for columns
 * that use the {@see MoneyValueType}. It sets up appropriate display formatting and
 * number-range filtering to ensure monetary values are properly presented and
 * filterable in datagrids.
 */
class MoneyValueColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    #[\Override]
    public function guessFormatter($class, $property, $type)
    {
        if (MoneyValueType::TYPE === $type) {
            $options = [
                'frontend_type' => 'string',
                'type' => MoneyValueType::TYPE,
            ];

            return new ColumnGuess($options, ColumnGuess::MEDIUM_CONFIDENCE);
        }

        return parent::guessFormatter($class, $property, $type);
    }

    #[\Override]
    public function guessFilter($class, $property, $type)
    {
        if (MoneyValueType::TYPE === $type) {
            $options = [
                'type' => 'number-range'
            ];

            return new ColumnGuess($options, ColumnGuess::HIGH_CONFIDENCE);
        }
        return parent::guessFilter($class, $property, $type);
    }
}
