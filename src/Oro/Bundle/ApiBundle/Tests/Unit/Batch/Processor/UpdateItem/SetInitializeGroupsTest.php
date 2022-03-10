<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\SetInitializeGroups;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;

class SetInitializeGroupsTest extends BatchUpdateItemProcessorTestCase
{
    /** @var SetInitializeGroups */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetInitializeGroups();
    }

    public function testProcessWithoutTargetAction()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target action is not defined.');

        $this->processor->process($this->context);
    }

    public function testProcessWithoutTargetContext()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The target context is not defined.');

        $this->context->setTargetAction('test');
        $this->processor->process($this->context);
    }

    public function testProcessForUnknownTargetAction()
    {
        $targetContext = $this->createMock(Context::class);
        $targetContext->expects(self::once())
            ->method('setFirstGroup')
            ->with(null);
        $targetContext->expects(self::never())
            ->method('setLastGroup');

        $this->context->setTargetAction('test');
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);
    }

    public function testProcessForCreateTargetAction()
    {
        $targetContext = $this->createMock(Context::class);
        $targetContext->expects(self::once())
            ->method('setFirstGroup')
            ->with(null);
        $targetContext->expects(self::once())
            ->method('setLastGroup')
            ->with(ApiActionGroup::INITIALIZE);

        $this->context->setTargetAction(ApiAction::CREATE);
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);
    }

    public function testProcessForUpdateTargetAction()
    {
        $targetContext = $this->createMock(Context::class);
        $targetContext->expects(self::once())
            ->method('setFirstGroup')
            ->with(null);
        $targetContext->expects(self::once())
            ->method('setLastGroup')
            ->with(ApiActionGroup::INITIALIZE);

        $this->context->setTargetAction(ApiAction::UPDATE);
        $this->context->setTargetContext($targetContext);
        $this->processor->process($this->context);
    }
}
