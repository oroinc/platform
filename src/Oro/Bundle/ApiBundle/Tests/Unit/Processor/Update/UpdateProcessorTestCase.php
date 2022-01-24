<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class UpdateProcessorTestCase extends FormProcessorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createContext(): FormContext
    {
        $context = new UpdateContext($this->configProvider, $this->metadataProvider);
        $context->setAction(ApiAction::UPDATE);

        return $context;
    }
}
