<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;

/**
 * The main processor for "customize_form_data" action.
 */
class CustomizeFormDataProcessor extends ByStepActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new CustomizeFormDataContext();
    }
}
