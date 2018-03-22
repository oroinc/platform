<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;

class EntityFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';
        $options = $options = $this->getOr(FilterUtility::FORM_OPTIONS_KEY, []);
        if ($this->isLazy() && isset($options['field_options']) && !isset($options['field_options']['choices'])) {
            $this->params[FilterUtility::FORM_OPTIONS_KEY]['field_options']['choices'] = [];
            $this->additionalOptions[] = ['field_options', 'choices'];
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EntityFilterType::class;
    }
}
