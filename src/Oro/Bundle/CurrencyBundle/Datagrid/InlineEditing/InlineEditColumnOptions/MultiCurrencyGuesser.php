<?php

namespace Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\GuesserInterface;

class MultiCurrencyGuesser implements GuesserInterface
{
    const MULTI_CURRENCY      = 'multi-currency';
    const DEFAULT_EDITOR_VIEW = 'orocurrency/js/app/views/editor/multi-currency-editor-view';
    const SAVE_API_ACCESSOR   = 'orocurrency/js/datagrid/inline-editing/currency-save-api-accessor';

    protected $currencyHelper;

    public function __construct(CurrencyNameHelper $currencyHelper)
    {
        $this->currencyHelper = $currencyHelper;
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
            && $column[PropertyInterface::FRONTEND_TYPE_KEY] === self::MULTI_CURRENCY) {
            if ($isEnabledInline) {
                $result[Configuration::BASE_CONFIG_KEY] = [Configuration::CONFIG_ENABLE_KEY => true];
            }

            $result[PropertyInterface::FRONTEND_TYPE_KEY] = self::MULTI_CURRENCY;
            $result[PropertyInterface::TYPE_KEY] = 'callback';

            if (empty($column[Configuration::CHOICES_KEY])) {
                $choices = $this->currencyHelper->getCurrencyChoices();
                $result[Configuration::CHOICES_KEY] = !empty($choices) ?
                    array_keys($choices) :
                    [];
            }

            $isConfiguredInlineEdit = array_key_exists(Configuration::BASE_CONFIG_KEY, $column);
            $result = $this->guessEditorView($column, $isConfiguredInlineEdit, $result, $columnName);
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     * @param $result
     * @param $columnName
     *
     * @return array
     */
    protected function guessEditorView($column, $isConfiguredInlineEdit, $result, $columnName)
    {
        if (!$this->isConfiguredViewEditor($column, $isConfiguredInlineEdit)) {
            $result[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY][Configuration::VIEW_KEY]
                = static::DEFAULT_EDITOR_VIEW;
            $result[Configuration::BASE_CONFIG_KEY][Configuration::SAVE_API_ACCESSOR_KEY][Configuration::CLASS_KEY]
                = self::SAVE_API_ACCESSOR;
            $result[Configuration::BASE_CONFIG_KEY][Configuration::SAVE_API_ACCESSOR_KEY]['cell_field'] = $columnName;
        }

        return $result;
    }

    /**
     * @param $column
     * @param $isConfiguredInlineEdit
     *
     * @return bool
     */
    protected function isConfiguredViewEditor($column, $isConfiguredInlineEdit)
    {
        $isConfigured = $isConfiguredInlineEdit
            && array_key_exists(Configuration::EDITOR_KEY, $column[Configuration::BASE_CONFIG_KEY]);
        $isConfigured = $isConfigured
            && array_key_exists(
                Configuration::VIEW_KEY,
                $column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]
            );

        return $isConfigured;
    }
}
