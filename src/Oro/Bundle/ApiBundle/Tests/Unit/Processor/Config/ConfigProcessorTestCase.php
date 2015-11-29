<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ConfigProcessorTestCase extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_NAME   = 'Test\Class';
    const TEST_VERSION      = '1.1';
    const TEST_REQUEST_TYPE = RequestType::REST_JSON_API;

    /** @var ConfigContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new ConfigContext();
        $this->context->setClassName(self::TEST_CLASS_NAME);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->setRequestType(self::TEST_REQUEST_TYPE);
    }
}
