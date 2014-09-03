<?php

namespace Oro\Bundle\EntityExtendBundle\Filter;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumFilterType;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class EnumFilter extends ChoiceFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';
        if (isset($params['null_value'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['null_value'] = $params['null_value'];
        }
        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return EnumFilterType::NAME;
    }
}
