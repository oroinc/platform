<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormStartHandleProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultFormStartHandleProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var DefaultFormStartHandleProcessor */
    protected $processor;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new DefaultFormStartHandleProcessor($this->doctrineHelper);

        $this->request = $this->createMock(Request::class);
    }

    public function testProcessSavedTrue()
    {
        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(
                [
                    'attribute_fields' => [
                        'to persist' => '...',
                        'not manageable' => '...',
                        'not an object' => '...',
                        'not in identity' => '...',
                        'not scheduled for insert' => '...',
                    ]
                ]
            );

        $this->request->expects($this->once())->method('isMethod')->with('POST')->willReturn(true);

        $toPersist = (object)['id' => 'toPersist'];
        $notManageable = (object)['id' => 'notManageable'];
        $notInIdentity = (object)['id' => 'notInIdentity'];
        $notScheduledForInsert = (object)['id' => 'notScheduledForInsert'];

        $workflowData = new WorkflowData([
            'to persist' => $toPersist,
            'not manageable' => $notManageable,
            'not an object' => [],
            'not in identity' => $notInIdentity,
            'not scheduled for insert' => $notScheduledForInsert
        ]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('handleRequest')->with($this->request);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->once())->method('getData')->willReturn($workflowData);

        $context = new TransitionContext();
        $context->setRequest($this->request);
        $context->setTransition($transition);
        $context->setForm($form);

        /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject $unitOfWork */
        $unitOfWork = $this->createMock(UnitOfWork::class);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getUnitOfWork')->willReturn($unitOfWork);

        $this->doctrineHelper->expects($this->any())->method('getEntityManager')->willReturn($entityManager);

        //not object will be filtered
        $this->doctrineHelper->expects($this->exactly(4))
            ->method('isManageableEntity')->withConsecutive(
                [$toPersist],
                [$notManageable],
                [$notInIdentity],
                [$notScheduledForInsert]
            )
            ->willReturnOnConsecutiveCalls(true, false, true, true);

        // not manageable entities will be filtered
        $unitOfWork->expects($this->exactly(3))
            ->method('isInIdentityMap')
            ->withConsecutive([$toPersist], [$notInIdentity], [$notScheduledForInsert])
            ->willReturnOnConsecutiveCalls(true, false, true);

        //those who in identity would not be checked as isScheduledForInsert
        $unitOfWork->expects($this->exactly(2))
            ->method('isScheduledForInsert')
            ->withConsecutive([$toPersist], [$notScheduledForInsert])
            ->willReturnOnConsecutiveCalls(true, false);

        //one entity scheduled for insert and one not in identity will be flushed
        $entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive([$toPersist], [$notInIdentity]);
        $entityManager->expects($this->exactly(2))
            ->method('flush')
            ->withConsecutive([$toPersist], [$notInIdentity]);

        $this->processor->process($context);

        $this->assertTrue($context->isSaved());
    }

    public function testSkipRequestMethodsOtherThanPost()
    {
        $context = new TransitionContext();
        $context->setRequest($this->request);

        $this->request->expects($this->once())->method('isMethod')->with('POST')->willReturn(false);

        $this->processor->process($context);

        $this->assertFalse($context->isSaved());
    }
}
