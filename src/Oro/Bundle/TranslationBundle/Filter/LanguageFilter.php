<?php

namespace Oro\Bundle\TranslationBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Form\Type\Filter\LanguageFilterType;

class LanguageFilter extends EntityFilter
{
    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['class'] = Language::class;
        $params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choice_label'] = 'code';

        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return LanguageFilterType::class;
    }
}
