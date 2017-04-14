<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultFormProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var DefaultFormProcessor */
    protected $processor;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new DefaultFormProcessor($this->doctrineHelper);

        $this->request = $this->createMock(Request::class);
    }

    public function testFormSubmit()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('submit')->with($this->request);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())->method('setUpdated');

        /** @var TransitionContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $context->expects($this->at(1))->method('setSaved')->with(false);
        $context->expects($this->once())->method('getForm')->willReturn($form);
        $context->expects($this->once())->method('getWorkflowItem')->willReturn($workflowItem);
        $context->expects($this->at(4))->method('setSaved')->with(true);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())->method('flush');

        $this->request->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(WorkflowItem::class)
            ->willReturn($entityManager);

        $this->processor->process($context);
    }

    public function testEnsureSavedFalse()
    {
        /** @var TransitionContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $context->expects($this->once())->method('setSaved')->with(false);
        $context->expects($this->never())->method('getForm');

        $this->request->expects($this->once())->method('isMethod')->with('POST')->willReturn(false);

        $this->processor->process($context);
    }

    public function testSavedFalseOnInvalidForm()
    {
        $this->request->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('submit')->with($this->request);
        $form->expects($this->once())->method('isValid')->willReturn(false);

        $context = new TransitionContext();
        $context->setRequest($this->request);
        $context->setForm($form);

        $this->doctrineHelper->expects($this->never())->method('getEntityManager');

        $this->processor->process($context);

        $this->assertFalse($context->isSaved());
    }
}
