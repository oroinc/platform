<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ParameterBag;
use PHPUnit\Framework\TestCase;

class CustomizeLoadedDataProcessorTestCase extends TestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected CustomizeLoadedDataContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = $this->createContext();
        $this->context->setAction('customize_loader_data');
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setSharedData(new ParameterBag());
    }

    protected function createContext(): CustomizeLoadedDataContext
    {
        return new CustomizeLoadedDataContext();
    }
}
