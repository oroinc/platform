<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\RequestType;

class OptionsProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var OptionsContext */
    protected $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    protected $metadataProvider;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new OptionsContext($this->configProvider, $this->metadataProvider);
        $this->context->setAction(ApiActions::OPTIONS);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
    }
}
