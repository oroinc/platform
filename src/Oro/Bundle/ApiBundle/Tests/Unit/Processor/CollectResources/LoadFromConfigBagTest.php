<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\Version;

class LoadFromConfigBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configBag;

    /** @var LoadFromConfigBag */
    protected $processor;

    protected function setUp()
    {
        $this->configBag = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigBag')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadFromConfigBag($this->configBag);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();
        $context->setVersion(Version::LATEST);

        $this->configBag->expects($this->once())
            ->method('getConfigs')
            ->with(Version::LATEST)
            ->willReturn(
                [
                    'Test\Entity1' => null,
                    'Test\Entity2' => null,
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            [
                'Test\Entity1' => new ApiResource('Test\Entity1'),
                'Test\Entity2' => new ApiResource('Test\Entity2'),
            ],
            $context->getResult()->toArray()
        );
    }
}
