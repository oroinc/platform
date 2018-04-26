<?php

namespace Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\CurrencyBundle\Converter\CurrencyToString;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;

class MultiCurrencyGuesser implements GuesserInterface
{
    const MULTI_CURRENCY_TYPE   = 'multi-currency';
    const MULTI_CURRENCY_CONFIG = 'multicurrency_config';
    const DEFAULT_EDITOR_VIEW   = 'orocurrency/js/app/views/editor/multi-currency-editor-view';
    const SAVE_API_ACCESSOR     = 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor';

    /**
     * @var array
     */
    protected $apiKeysToMultiCurrencyConfigKeysMapper = [
        'cell_field'     => 'original_field',
        'currency_field' => 'currency_field',
        'value_field'    => 'value_field'
    ];

    /**
     * @var CurrencyNameHelper
     */
    protected $currencyHelper;

    /**
     * @var CurrencyToString
     */
    protected $currencyToStringConverter;

    public function __construct(CurrencyNameHelper $currencyHelper, CurrencyToString $currencyToString)
    {
        $this->currencyHelper = $currencyHelper;
        $this->currencyToStringConverter = $currencyToString;
    }

    /**
     * @param string $columnName
     * @param string $entityName
     * @param array $column
     * @param bool $isEnabledInline
     * @return array
     */
    public function guessColumnOptions($columnName, $entityName, $column, $isEnabledInline = false)
    {
        $result = [];
        if (array_key_exists(PropertyInterface::FRONTEND_TYPE_KEY, $column)
            && $column[PropertyInterface::FRONTEND_TYPE_KEY] === self::MULTI_CURRENCY_TYPE) {
            $result = $this->getColumnConvertationOptions($column);

            if (empty($column[Configuration::CHOICES_KEY])) {
                $choices = $this->currencyHelper->getCurrencyChoices();
                $result[Configuration::CHOICES_KEY] = !empty($choices) ?
                    array_values($choices) :
                    [];
            }

            $inlineOptions = $this->getInlineOptions($column, $isEnabledInline);
            if (!empty($inlineOptions)) {
                $result[Configuration::BASE_CONFIG_KEY] = $inlineOptions;
            }
        }

        return $result;
    }

    /**
     * @param array $column
     *
     * @return array
     */
    protected function getColumnConvertationOptions(array $column)
    {
        if (isset($column[PropertyInterface::TYPE_KEY]) && $column[PropertyInterface::TYPE_KEY] === 'callback') {
            return [];
        }

        return [
            PropertyInterface::TYPE_KEY => 'callback',
            'callable' => [
                $this->currencyToStringConverter,
                'convert'
            ]
        ];
    }

    /**
     * @param array $column
     * @param bool  $isEnabledInline
     *
     * @return array
     */
    protected function getInlineOptions(array $column, $isEnabledInline)
    {
        $inlineOptions = [];

        if (!$this->isConfiguredViewEditor($column)) {
            $inlineOptions = [
                Configuration::EDITOR_KEY => [
                    Configuration::VIEW_KEY => static::DEFAULT_EDITOR_VIEW
                ],
                Configuration::SAVE_API_ACCESSOR_KEY => [
                    Configuration::CLASS_KEY => self::SAVE_API_ACCESSOR,
                ],
            ];

            $apiAccessorConfig = &$inlineOptions[Configuration::SAVE_API_ACCESSOR_KEY];
            foreach ($this->apiKeysToMultiCurrencyConfigKeysMapper as $apiKey => $multiCurrencyConfigKey) {
                $apiAccessorConfig[$apiKey] = $column[self::MULTI_CURRENCY_CONFIG][$multiCurrencyConfigKey];
            }
        }

        if ($isEnabledInline) {
            $inlineOptions[Configuration::CONFIG_ENABLE_KEY] = true;
        }

        return $inlineOptions;
    }

    /**
     * @param $column
     *
     * @return bool
     */
    protected function isConfiguredViewEditor($column)
    {
        $isConfigured = array_key_exists(Configuration::BASE_CONFIG_KEY, $column)
            && array_key_exists(Configuration::EDITOR_KEY, $column[Configuration::BASE_CONFIG_KEY]);

        $isConfigured = $isConfigured
            && array_key_exists(
                Configuration::VIEW_KEY,
                $column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]
            );

        return $isConfigured;
    }
}
