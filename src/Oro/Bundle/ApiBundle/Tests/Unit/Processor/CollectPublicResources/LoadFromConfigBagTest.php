<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectPublicResources;

use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\CollectPublicResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Request\PublicResource;
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
        $context = new CollectPublicResourcesContext();
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
                new PublicResource('Test\Entity1'),
                new PublicResource('Test\Entity2'),
            ],
            $context->getResult()->toArray()
        );
    }
}
