<?php

namespace Oro\Bundle\TranslationBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;

/**
 * The filter by a language.
 */
class LanguageFilter extends EntityFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FORM_OPTIONS_KEY][self::FIELD_OPTIONS_KEY]['class'] = Language::class;
        $params[FilterUtility::FORM_OPTIONS_KEY][self::FIELD_OPTIONS_KEY]['choice_label'] = 'code';

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return LanguageFilterType::class;
    }
}
