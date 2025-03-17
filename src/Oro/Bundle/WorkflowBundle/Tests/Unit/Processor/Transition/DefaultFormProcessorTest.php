<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultFormProcessorTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private Request&MockObject $request;
    private DefaultFormProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->request = $this->createMock(Request::class);

        $this->processor = new DefaultFormProcessor($this->doctrineHelper);
    }

    public function testFormSubmit(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($this->request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('setUpdated');

        $context = $this->createMock(TransitionContext::class);
        $context->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects(self::once())
            ->method('getForm')
            ->willReturn($form);
        $context->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);
        $context->expects(self::exactly(2))
            ->method('setSaved')
            ->withConsecutive([false], [true]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('flush');

        $this->request->expects(self::once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(WorkflowItem::class)
            ->willReturn($entityManager);

        $this->processor->process($context);
    }

    public function testEnsureSavedFalse(): void
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects(self::once())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects(self::once())
            ->method('setSaved')
            ->with(false);
        $context->expects(self::never())
            ->method('getForm');

        $this->request->expects(self::once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(false);

        $this->processor->process($context);
    }

    public function testSavedFalseOnInvalidForm(): void
    {
        $this->request->expects(self::once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($this->request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $context = new TransitionContext();
        $context->setRequest($this->request);
        $context->setForm($form);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->processor->process($context);

        self::assertFalse($context->isSaved());
    }
}
