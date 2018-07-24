<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\CollectResources\AddExcludedActionsForNotAccessibleResources;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

class AddExcludedActionsForNotAccessibleResourcesTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorBagInterface */
    private $actionProcessorBag;

    /** @var AddExcludedActionsForNotAccessibleResources */
    private $processor;

    protected function setUp()
    {
        $this->actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);

        $this->processor = new AddExcludedActionsForNotAccessibleResources($this->actionProcessorBag);
    }

    public function testProcess()
    {
        $accessibleResources = ['Test\Class1'];
        $allActions = ['action1', 'action2'];

        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');
        $resource2->addExcludedAction('action2');

        $resources = new ApiResourceCollection();
        $resources->add($resource1);
        $resources->add($resource2);

        $this->actionProcessorBag->expects(self::once())
            ->method('getActions')
            ->willReturn($allActions);

        $context = new CollectResourcesContext();
        $context->setAccessibleResources($accessibleResources);
        $context->setResult($resources);

        $this->processor->process($context);

        self::assertSame([], $resource1->getExcludedActions());
        self::assertSame(['action2'], $resource2->getExcludedActions());
    }
}
