<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultFormProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var DefaultFormProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->request = $this->createMock(Request::class);

        $this->processor = new DefaultFormProcessor($this->doctrineHelper);
    }

    public function testFormSubmit()
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('setUpdated');

        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects($this->once())
            ->method('getForm')
            ->willReturn($form);
        $context->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);
        $context->expects($this->exactly(2))
            ->method('setSaved')
            ->withConsecutive([false], [true]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('flush');

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(WorkflowItem::class)
            ->willReturn($entityManager);

        $this->processor->process($context);
    }

    public function testEnsureSavedFalse()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects($this->once())
            ->method('setSaved')
            ->with(false);
        $context->expects($this->never())
            ->method('getForm');

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(false);

        $this->processor->process($context);
    }

    public function testSavedFalseOnInvalidForm()
    {
        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $context = new TransitionContext();
        $context->setRequest($this->request);
        $context->setForm($form);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->processor->process($context);

        $this->assertFalse($context->isSaved());
    }
}
