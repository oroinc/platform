<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class CreateProcessorTestCase extends FormProcessorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createContext(): FormContext
    {
        $context = new CreateContext($this->configProvider, $this->metadataProvider);
        $context->setAction(ApiAction::CREATE);

        return $context;
    }
}
