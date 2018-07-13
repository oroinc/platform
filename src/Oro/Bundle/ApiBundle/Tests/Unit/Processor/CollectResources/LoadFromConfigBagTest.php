<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\Version;

class LoadFromConfigBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigBagInterface */
    private $configBag;

    /** @var LoadFromConfigBag */
    private $processor;

    protected function setUp()
    {
        $this->configBag = $this->createMock(ConfigBagInterface::class);

        $configBagRegistry = $this->createMock(ConfigBagRegistry::class);
        $configBagRegistry->expects(self::any())
            ->method('getConfigBag')
            ->willReturn($this->configBag);

        $this->processor = new LoadFromConfigBag($configBagRegistry);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();
        $context->setVersion(Version::LATEST);

        $this->configBag->expects(self::once())
            ->method('getClassNames')
            ->with(Version::LATEST)
            ->willReturn(['Test\Entity1', 'Test\Entity2']);

        $this->processor->process($context);

        self::assertEquals(
            [
                'Test\Entity1' => new ApiResource('Test\Entity1'),
                'Test\Entity2' => new ApiResource('Test\Entity2')
            ],
            $context->getResult()->toArray()
        );
    }
}
