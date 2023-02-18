<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;

/**
 * The main processor for "customize_loaded_data" action.
 */
class CustomizeLoadedDataProcessor extends ByStepActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): CustomizeLoadedDataContext
    {
        return new CustomizeLoadedDataContext();
    }
}
