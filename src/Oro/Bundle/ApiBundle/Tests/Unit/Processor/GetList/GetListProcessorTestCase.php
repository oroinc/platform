<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;

class GetListProcessorTestCase extends \PHPUnit_Framework_TestCase
{
    const TEST_VERSION      = '1.1';
    const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var GetListContext */
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

        $this->context = new GetListContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras(
            [
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FiltersConfigExtra(),
                new SortersConfigExtra()
            ]
        );
    }
}
