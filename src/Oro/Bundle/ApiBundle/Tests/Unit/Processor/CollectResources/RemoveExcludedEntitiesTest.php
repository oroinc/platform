<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\RemoveExcludedEntities;
use Oro\Bundle\ApiBundle\Provider\ExclusionProviderRegistry;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

class RemoveExcludedEntitiesTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ExclusionProviderRegistry */
    private $exclusionProviderRegistry;

    /** @var RemoveExcludedEntities */
    private $processor;

    protected function setUp()
    {
        $this->exclusionProviderRegistry = $this->createMock(ExclusionProviderRegistry::class);

        $this->processor = new RemoveExcludedEntities($this->exclusionProviderRegistry);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();
        $context->setVersion(Version::LATEST);

        $context->getResult()->add(new ApiResource('Test\Entity1'));
        $context->getResult()->add(new ApiResource('Test\Entity2'));

        $exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->exclusionProviderRegistry->expects(self::once())
            ->method('getExclusionProvider')
            ->with(self::identicalTo($context->getRequestType()))
            ->willReturn($exclusionProvider);
        $exclusionProvider->expects(self::exactly(2))
            ->method('isIgnoredEntity')
            ->willReturnMap(
                [
                    ['Test\Entity1', true],
                    ['Test\Entity1', false]
                ]
            );

        $this->processor->process($context);

        self::assertEquals(
            [
                'Test\Entity2' => new ApiResource('Test\Entity2')
            ],
            $context->getResult()->toArray()
        );
    }
}
