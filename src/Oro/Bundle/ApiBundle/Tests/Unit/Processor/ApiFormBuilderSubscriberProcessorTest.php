<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ApiFormBuilderSubscriberProcessor;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ApiFormBuilderSubscriberProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventSubscriberInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventSubscriber;

    /**
     * @var ApiFormBuilderSubscriberProcessor
     */
    protected $testedProcessor;

    public function setUp()
    {
        $this->eventSubscriber = $this->createMock(EventSubscriberInterface::class);

        $this->testedProcessor = new ApiFormBuilderSubscriberProcessor($this->eventSubscriber);
    }

    /**
     * @return FormContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContextMock()
    {
        return $this->createMock(FormContext::class);
    }

    /**
     * @return FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createFormBuilderMock()
    {
        return $this->createMock(FormBuilderInterface::class);
    }

    public function testSuccessfulProcess()
    {
        $contextMock = $this->createContextMock();
        $formBuilderMock = $this->createFormBuilderMock();

        $contextMock
            ->expects(static::once())
            ->method('hasFormBuilder')
            ->willReturn(true);

        $contextMock
            ->expects(static::once())
            ->method('hasForm')
            ->willReturn(false);

        $contextMock
            ->expects(static::once())
            ->method('getFormBuilder')
            ->willReturn($formBuilderMock);

        $formBuilderMock
            ->expects(static::once())
            ->method('addEventSubscriber')
            ->with($this->eventSubscriber);

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongContext()
    {
        $contextMock = $this->createMock(ContextInterface::class);

        $contextMock
            ->expects(static::never())
            ->method(static::anything());

        $this->testedProcessor->process($contextMock);
    }

    public function testHasNoFormBuilder()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('hasFormBuilder')
            ->willReturn(false);

        $contextMock
            ->expects(static::never())
            ->method('getFormBuilder');

        $this->testedProcessor->process($contextMock);
    }

    public function testHasNoForm()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('hasForm')
            ->willReturn(true);

        $contextMock
            ->expects(static::never())
            ->method('getFormBuilder');

        $this->testedProcessor->process($contextMock);
    }
}
