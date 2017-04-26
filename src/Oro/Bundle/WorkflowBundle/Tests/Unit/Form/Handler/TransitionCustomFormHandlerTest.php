<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\Handler\TransitionCustomFormHandler;
use Oro\Bundle\WorkflowBundle\Form\Handler\TransitionFormHandler;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;

class TransitionCustomFormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormHandlerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $formHandlerRegistry;

    /** @var FormHandlerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formHandler;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowItem;

    /** @var WorkflowData|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowItemData;

    /** @var Transition|\PHPUnit_Framework_MockObject_MockObject */
    protected $transition;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var StubEntity|\PHPUnit_Framework_MockObject_MockObject */
    protected $entity;

    /** @var TransitionFormHandler */
    private $transitionCustomFormHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formHandlerRegistry = $this->createMock(FormHandlerRegistry::class);
        $this->formHandler = $this->createMock(FormHandlerInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->workflowItem = $this->createMock(WorkflowItem::class);
        $this->workflowItemData = $this->createMock(WorkflowData::class);
        $this->transition = $this->createMock(Transition::class);
        $this->request = $this->createMock(Request::class);
        $this->entity = $this->createMock(StubEntity::class);

        $this->transitionCustomFormHandler = new TransitionCustomFormHandler($this->formHandlerRegistry);
    }

    /**
     * @dataProvider resultDataProvider
     *
     * @param bool $expected
     */
    public function testProcessStartTransitionForm($expected)
    {
        $this->assertFormHandlerRegistryCalled($expected);
        $result = $this->transitionCustomFormHandler->processStartTransitionForm(
            $this->form,
            $this->workflowItem,
            $this->transition,
            $this->request
        );
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider resultDataProvider
     *
     * @param bool $expected
     */
    public function testProcessTransitionForm($expected)
    {
        $this->assertFormHandlerRegistryCalled($expected);
        $result = $this->transitionCustomFormHandler->processStartTransitionForm(
            $this->form,
            $this->workflowItem,
            $this->transition,
            $this->request
        );
        $this->assertSame($expected, $result);
    }

    /**
     * @return \Generator
     */
    public function resultDataProvider()
    {
        yield 'positive' => ['expected' => true];
        yield 'negative' => ['expected' => false];
    }

    /**
     * @param bool $result
     */
    protected function assertFormHandlerRegistryCalled($result = false)
    {
        $this->transition->expects($this->once())->method('getFormHandler')->willReturn('handler');
        $this->transition->expects($this->once())->method('getFormDataAttribute')->willReturn('data_attribute');
        $this->workflowItemData->expects($this->once())
            ->method('get')
            ->with('data_attribute')
            ->willReturn($this->entity);
        $this->workflowItem->expects($this->once())->method('getData')->willReturn($this->workflowItemData);

        $this->formHandlerRegistry->expects($this->once())->method('get')
            ->with('handler')
            ->willReturn($this->formHandler);

        $this->formHandler->expects($this->once())->method('process')->with(
            $this->entity,
            $this->form,
            $this->request
        )->willReturn($result);
    }
}
