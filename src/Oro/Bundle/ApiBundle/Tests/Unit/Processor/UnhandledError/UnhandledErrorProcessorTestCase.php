<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\UnhandledError;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

class UnhandledErrorProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected Context $context;

    protected function setUp(): void
    {
        $this->context = new Context(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
        $this->context->setAction('unhandled_error');
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfig(null);
        $this->context->setMetadata(null);
    }
}
