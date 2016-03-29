<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class DeleteListProcessorTestCase extends \PHPUnit_Framework_TestCase
{
    const TEST_VERSION      = '1.1';
    const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var DeleteListContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new DeleteListContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
    }
}
