<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeVersion;

class NormalizeVersionTest extends \PHPUnit_Framework_TestCase
{
    /** @var NormalizeVersion */
    protected $processor;

    /** @var Context */
    protected $context;

    public function setUp()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new Context($configProvider, $metadataProvider);
        $this->processor = new NormalizeVersion();
    }

    public function testProcessOnEmptyVersion()
    {
        $this->processor->process($this->context);
        $this->assertEquals('latest', $this->context->getVersion());
    }

    public function testProcessOnVersionWithString()
    {
        $this->context->setVersion('v1.2');
        $this->processor->process($this->context);
        $this->assertEquals('1.2', $this->context->getVersion());
    }

    public function testProcess()
    {
        $version = '2.1';
        $this->context->setVersion($version);
        $this->processor->process($this->context);
        $this->assertSame($version, $this->context->getVersion());
    }
}
