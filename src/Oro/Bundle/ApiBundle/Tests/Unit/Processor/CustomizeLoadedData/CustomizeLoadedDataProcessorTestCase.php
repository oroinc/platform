<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ParameterBag;

class CustomizeLoadedDataProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var CustomizeLoadedDataContext */
    protected $context;

    protected function setUp(): void
    {
        $this->context = $this->createContext();
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setSharedData(new ParameterBag());
    }

    /**
     * @return CustomizeLoadedDataContext
     */
    protected function createContext()
    {
        return new CustomizeLoadedDataContext();
    }
}
