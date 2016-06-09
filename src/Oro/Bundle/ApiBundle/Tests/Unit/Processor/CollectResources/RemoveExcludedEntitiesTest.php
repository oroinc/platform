<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\RemoveExcludedEntities;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\Version;

class RemoveExcludedEntitiesTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityExclusionProvider;

    /** @var RemoveExcludedEntities */
    protected $processor;

    protected function setUp()
    {
        $this->entityExclusionProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface');

        $this->processor = new RemoveExcludedEntities($this->entityExclusionProvider);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();
        $context->setVersion(Version::LATEST);

        $context->getResult()->add(new ApiResource('Test\Entity1'));
        $context->getResult()->add(new ApiResource('Test\Entity2'));

        $this->entityExclusionProvider->expects($this->exactly(2))
            ->method('isIgnoredEntity')
            ->willReturnMap(
                [
                    ['Test\Entity1', true],
                    ['Test\Entity1', false],
                ]
            );

        $this->processor->process($context);

        $this->assertEquals(
            [
                'Test\Entity2' => new ApiResource('Test\Entity2'),
            ],
            $context->getResult()->toArray()
        );
    }
}
