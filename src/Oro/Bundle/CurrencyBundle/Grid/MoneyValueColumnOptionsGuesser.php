<?php

namespace Oro\Bundle\CurrencyBundle\Grid;

use Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types\MoneyValueType;
use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

class MoneyValueColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
