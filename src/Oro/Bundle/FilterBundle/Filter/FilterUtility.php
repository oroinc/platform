<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

/**
 * Provides a set of constants for datagrid filters.
 */
class FilterUtility
{
    public const CONDITION_OR  = 'OR';
    public const CONDITION_AND = 'AND';

    public const CONDITION_KEY        = 'filter_condition';
    public const BY_HAVING_KEY        = 'filter_by_having';
    public const RENDERABLE_KEY       = 'renderable';
    public const VISIBLE_KEY          = 'visible';
    public const DISABLED_KEY         = PropertyInterface::DISABLED_KEY;
    public const TYPE_KEY             = 'type';
    public const FRONTEND_TYPE_KEY    = 'ftype';
    public const DATA_NAME_KEY        = 'data_name';
    public const TRANSLATABLE_KEY     = 'translatable';
    public const MIN_LENGTH_KEY       = 'min_length';
    public const MAX_LENGTH_KEY       = 'max_length';
    public const FORCE_LIKE_KEY       = 'force_like';
    public const FORM_OPTIONS_KEY     = 'options';
    public const DIVISOR_KEY          = 'divisor';
    public const TYPE_EMPTY           = 'filter_empty_option';
    public const TYPE_NOT_EMPTY       = 'filter_not_empty_option';
    public const CASE_INSENSITIVE_KEY = 'case_insensitive';
    public const VALUE_CONVERSION_KEY = 'value_conversion';

    public function getParamMap(): array
    {
        return [
            self::FRONTEND_TYPE_KEY => self::TYPE_KEY,
            'template_theme'        => 'templateTheme',
        ];
    }

    /**
     * @return string[]
     */
    public function getExcludeParams(): array
    {
        return [self::DATA_NAME_KEY, self::FORM_OPTIONS_KEY];
    }
}
