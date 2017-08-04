<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class CreateProcessorTestCase extends FormProcessorTestCase
{
    /**
     * @return CreateContext
     */
    protected function createContext()
    {
        return new CreateContext($this->configProvider, $this->metadataProvider);
    }
}
