<?php

namespace Oro\Bundle\CurrencyBundle\Grid;

use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;

/**
 * Provides automatic configuration for currency-type datagrid columns.
 *
 * This guesser automatically configures filter options for columns that contain
 * currency codes. It sets up a choice filter with all available currencies,
 * allowing users to filter datagrid data by selecting one or more currencies.
 */
class CurrencyColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    /**
     * @var CurrencyNameHelper
     */
    protected $currencyHelper;

    public function __construct(CurrencyNameHelper $currencyHelper)
    {
        $this->currencyHelper = $currencyHelper;
    }

    #[\Override]
    public function guessFilter($class, $property, $type)
    {
        if ('currency' === $type) {
            $options = [
                'type' => 'choice',
                'options' => [
                    'field_options' => [
                        'choices' => $this->currencyHelper->getCurrencyChoices('full_name'),
                        'multiple' => true
                    ]
                ]
            ];

            return new ColumnGuess($options, ColumnGuess::MEDIUM_CONFIDENCE);
        } else {
            return parent::guessFilter($class, $property, $type);
        }
    }
}
