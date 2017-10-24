<?php

namespace Oro\Bundle\FilterBundle\Filter;

class FilterUtility
{
    const CONDITION_OR  = 'OR';
    const CONDITION_AND = 'AND';

    const CONDITION_KEY     = 'filter_condition';
    const BY_HAVING_KEY     = 'filter_by_having';
    const ENABLED_KEY       = 'enabled';
    const VISIBLE_KEY       = 'visible';
    const TYPE_KEY          = 'type';
    const FRONTEND_TYPE_KEY = 'ftype';
    const DATA_NAME_KEY     = 'data_name';
    const TRANSLATABLE_KEY  = 'translatable';
    const MIN_LENGTH_KEY    = 'min_length';
    const MAX_LENGTH_KEY    = 'max_length';
    const FORCE_LIKE_KEY    = 'force_like';
    const FORM_OPTIONS_KEY  = 'options';
    const DIVISOR_KEY       = 'divisor';
    const TYPE_EMPTY        = 'filter_empty_option';
    const TYPE_NOT_EMPTY    = 'filter_not_empty_option';
    const CASE_INSENSITIVE_KEY = 'case_insensitive';
    const VALUE_CONVERSION_KEY = 'value_conversion';

    public function getParamMap()
    {
        return [
            self::FRONTEND_TYPE_KEY => self::TYPE_KEY,
            'template_theme'        => 'templateTheme',
        ];
    }

    public function getExcludeParams()
    {
        return [self::DATA_NAME_KEY, self::FORM_OPTIONS_KEY];
    }
}
