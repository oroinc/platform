<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\DefaultFormStartHandleProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultFormStartHandleProcessorTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private DefaultFormStartHandleProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new DefaultFormStartHandleProcessor($this->doctrineHelper);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessSavedTrue(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects(self::once())
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

        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);

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

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('handleRequest')
            ->with($request);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn($workflowData);

        $context = new TransitionContext();
        $context->setRequest($request);
        $context->setTransition($transition);
        $context->setForm($form);

        $unitOfWork = $this->createMock(UnitOfWork::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        // not object will be filtered
        $this->doctrineHelper->expects(self::exactly(4))
            ->method('isManageableEntity')
            ->withConsecutive(
                [$toPersist],
                [$notManageable],
                [$notInIdentity],
                [$notScheduledForInsert]
            )
            ->willReturnOnConsecutiveCalls(true, false, true, true);

        // not manageable entities will be filtered
        $unitOfWork->expects(self::exactly(3))
            ->method('isInIdentityMap')
            ->withConsecutive([$toPersist], [$notInIdentity], [$notScheduledForInsert])
            ->willReturnOnConsecutiveCalls(true, false, true);

        // those who in identity would not be checked as isScheduledForInsert
        $unitOfWork->expects(self::exactly(2))
            ->method('isScheduledForInsert')
            ->withConsecutive([$toPersist], [$notScheduledForInsert])
            ->willReturnOnConsecutiveCalls(true, false);

        // one entity scheduled for insert and one not in identity will be flushed
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->withConsecutive([$toPersist], [$notInIdentity]);
        $entityManager->expects(self::exactly(2))
            ->method('flush')
            ->withConsecutive([$toPersist], [$notInIdentity]);

        $this->processor->process($context);

        self::assertTrue($context->isSaved());
    }

    public function testSkipRequestMethodsOtherThanPost(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(false);

        $context = new TransitionContext();
        $context->setRequest($request);

        $this->processor->process($context);

        self::assertFalse($context->isSaved());
    }
}
